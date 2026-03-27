<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Scanner;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Port\ProjectScannerInterface;
use App\Catalog\Domain\Port\StackDetectorInterface;
use App\Catalog\Infrastructure\Scanner\Detector\PhpDetector;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\DTO\ScanResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ProjectScanner implements ProjectScannerInterface
{
    /** @var list<StackDetectorInterface> */
    private readonly array $detectorList;

    /** @var list<string> */
    private readonly array $allManifests;

    /**
     * @param iterable<StackDetectorInterface> $detectors
     */
    public function __construct(
        private readonly GitProviderFactoryInterface $gitProviderFactory,
        #[AutowireIterator('catalog.stack_detector')]
        iterable $detectors,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->detectorList = \array_values($detectors instanceof \Traversable ? \iterator_to_array($detectors) : $detectors);

        $manifests = [];
        foreach ($this->detectorList as $detector) {
            foreach ($detector->supportedManifests() as $manifest) {
                $manifests[$manifest] = true;
            }
        }
        $this->allManifests = \array_keys($manifests);
    }

    public function scan(Project $project): ScanResult
    {
        try {
            return $this->doScan($project);
        } catch (\Throwable $e) {
            $this->logger->error('Scan failed for project {project}: {error}', [
                'project' => $project->getId()->toRfc4122(),
                'error' => $e->getMessage(),
            ]);

            return new ScanResult(stacks: [], dependencies: []);
        }
    }

    private function doScan(Project $project): ScanResult
    {
        $provider = $project->getProvider();
        $externalId = $project->getExternalId();
        $ref = $project->getDefaultBranch();

        if ($provider === null || $externalId === null) {
            return new ScanResult(stacks: [], dependencies: []);
        }

        $gitProvider = $this->gitProviderFactory->create($provider);
        $searchPaths = $this->discoverSearchPaths($gitProvider, $project);

        /** @var list<DetectedStack> $stacks */
        $stacks = [];
        /** @var list<DetectedDependency> $dependencies */
        $dependencies = [];

        $rootNpmLockVersions = $this->resolveNpmLockVersions($gitProvider, $provider, $externalId, '', $ref);

        foreach ($searchPaths as $basePath) {
            $prefix = $basePath === '' ? '' : $basePath . '/';

            $manifestContents = [];
            foreach ($this->allManifests as $manifest) {
                $content = $gitProvider->getFileContent($provider, $externalId, $prefix . $manifest, $ref);
                if ($content !== null) {
                    $manifestContents[$manifest] = $content;
                }
            }

            if (isset($manifestContents['requirements.txt'])) {
                unset($manifestContents['pyproject.toml']);
            }

            foreach ($this->detectorList as $detector) {
                $supported = $detector->supportedManifests();
                $relevant = \array_intersect_key($manifestContents, \array_flip($supported));

                if ($relevant === []) {
                    continue;
                }

                $detectedStacks = $detector->detect($relevant);
                $detectedDeps = $detector->extractDependencies($relevant);

                if (\array_intersect($supported, ['composer.json', 'composer.lock']) !== []) {
                    $composerLock = $manifestContents['composer.lock'] ?? null;
                    if ($composerLock !== null) {
                        $lockData = \json_decode($composerLock, true);
                        if (\is_array($lockData)) {
                            /** @var array<string, mixed> $lockData */
                            $lockVersions = VersionHelper::parseComposerLock($lockData);
                            $phpVersion = VersionHelper::extractComposerPhpVersion($lockData);

                            $detectedStacks = $detector->enrichStackVersions($detectedStacks, $lockVersions);
                            $detectedDeps = $detector->enrichDependencyVersions($detectedDeps, $lockVersions);

                            if ($detector instanceof PhpDetector) {
                                $detectedDeps = $detector->enrichDependencyUrls($detectedDeps, $manifestContents);

                                if ($phpVersion !== null) {
                                    $detectedStacks = \array_map(
                                        static fn (DetectedStack $s): DetectedStack => $s->language === 'PHP'
                                            ? new DetectedStack($s->language, $s->framework, \ltrim($phpVersion, 'v'), $s->frameworkVersion)
                                            : $s,
                                        $detectedStacks,
                                    );
                                }
                            }
                        }
                    }
                }

                if (\in_array('package.json', $supported, true) && isset($manifestContents['package.json'])) {
                    $localNpmLockVersions = $prefix !== '' ? $this->resolveNpmLockVersions($gitProvider, $provider, $externalId, $prefix, $ref) : [];
                    $npmLockVersions = \array_merge($rootNpmLockVersions, $localNpmLockVersions);
                    if ($npmLockVersions !== []) {
                        $detectedStacks = $detector->enrichStackVersions($detectedStacks, $npmLockVersions);
                        $detectedDeps = $detector->enrichDependencyVersions($detectedDeps, $npmLockVersions);
                    }
                }

                \array_push($stacks, ...$detectedStacks);
                \array_push($dependencies, ...$detectedDeps);
            }
        }

        return new ScanResult(
            stacks: $this->deduplicateStacks($stacks),
            dependencies: $this->deduplicateDependencies($dependencies),
        );
    }

    /** @return list<string> */
    private function discoverSearchPaths(GitProviderInterface $gitProvider, Project $project): array
    {
        $provider = $project->getProvider();
        $externalId = $project->getExternalId();
        $ref = $project->getDefaultBranch();

        if ($provider === null || $externalId === null) {
            return [''];
        }

        $paths = [''];

        $rootEntries = $gitProvider->listDirectory($provider, $externalId, '', $ref);

        foreach ($rootEntries as $entry) {
            if ($entry['type'] !== 'tree') {
                continue;
            }

            $subEntries = $gitProvider->listDirectory($provider, $externalId, $entry['path'], $ref);
            $hasManifest = false;

            foreach ($subEntries as $subEntry) {
                if ($subEntry['type'] === 'blob' && \in_array($subEntry['name'], $this->allManifests, true)) {
                    $hasManifest = true;
                    break;
                }
            }

            if ($hasManifest) {
                $paths[] = $entry['path'];
            }
        }

        return $paths;
    }

    /** @return array<string, string> */
    private function resolveNpmLockVersions(
        GitProviderInterface $gitProvider,
        Provider $provider,
        string $externalId,
        string $prefix,
        string $ref,
    ): array {
        $pnpmLock = $gitProvider->getFileContent($provider, $externalId, $prefix . 'pnpm-lock.yaml', $ref);
        if ($pnpmLock !== null) {
            return VersionHelper::parsePnpmLock($pnpmLock);
        }

        $npmLock = $gitProvider->getFileContent($provider, $externalId, $prefix . 'package-lock.json', $ref);
        if ($npmLock !== null) {
            $data = \json_decode($npmLock, true);
            if (\is_array($data)) {
                /** @var array<string, mixed> $data */
                return VersionHelper::parseNpmLock($data);
            }
        }

        $yarnLock = $gitProvider->getFileContent($provider, $externalId, $prefix . 'yarn.lock', $ref);
        if ($yarnLock !== null) {
            return VersionHelper::parseYarnLock($yarnLock);
        }

        return [];
    }

    /**
     * @param list<DetectedStack> $stacks
     * @return list<DetectedStack>
     */
    private function deduplicateStacks(array $stacks): array
    {
        /** @var array<string, list<DetectedStack>> $byLanguage */
        $byLanguage = [];

        foreach ($stacks as $stack) {
            $byLanguage[$stack->language][] = $stack;
        }

        $result = [];
        foreach ($byLanguage as $langStacks) {
            $withFramework = \array_filter($langStacks, static fn (DetectedStack $s): bool => $s->framework !== 'none');

            if ($withFramework !== []) {
                $seen = [];
                foreach ($withFramework as $s) {
                    if (!isset($seen[$s->framework])) {
                        $seen[$s->framework] = true;
                        $result[] = $s;
                    }
                }
            } else {
                $result[] = $langStacks[0];
            }
        }

        return $result;
    }

    /**
     * @param list<DetectedDependency> $dependencies
     * @return list<DetectedDependency>
     */
    private function deduplicateDependencies(array $dependencies): array
    {
        $seen = [];
        $result = [];

        foreach ($dependencies as $dep) {
            $key = $dep->packageManager->value . ':' . $dep->name;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $dep;
        }

        return $result;
    }
}

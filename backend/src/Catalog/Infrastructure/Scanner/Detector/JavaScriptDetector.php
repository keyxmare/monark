<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Scanner\Detector;

use App\Catalog\Domain\Port\StackDetectorInterface;
use App\Catalog\Infrastructure\Scanner\VersionHelper;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('catalog.stack_detector')]
class JavaScriptDetector implements StackDetectorInterface
{
    private const array FRAMEWORK_MAP = [
        'nuxt' => 'Nuxt',
        '@nuxt/core' => 'Nuxt',
        'next' => 'Next.js',
        'vue' => 'Vue',
        'react' => 'React',
        '@angular/core' => 'Angular',
        'angular' => 'Angular',
        'svelte' => 'Svelte',
        'astro' => 'Astro',
        'remix' => 'Remix',
    ];

    private const array FRAMEWORK_LOCK_PACKAGES = [
        'Vue' => 'vue',
        'Nuxt' => 'nuxt',
        'React' => 'react',
        'Next.js' => 'next',
        'Svelte' => 'svelte',
        'Astro' => 'astro',
        'Remix' => 'remix',
    ];

    public function supportedManifests(): array
    {
        return ['package.json'];
    }

    public function detect(array $manifestContents): array
    {
        $packageJson = $manifestContents['package.json'] ?? null;
        if ($packageJson === null) {
            return [];
        }

        $data = \json_decode($packageJson, true);
        if (!\is_array($data)) {
            return [];
        }

        /** @var array<string, string> $deps */
        $deps = \is_array($data['dependencies'] ?? null) ? $data['dependencies'] : [];
        /** @var array<string, string> $devDeps */
        $devDeps = \is_array($data['devDependencies'] ?? null) ? $data['devDependencies'] : [];
        /** @var array<string, string> $allDeps */
        $allDeps = \array_merge($deps, $devDeps);

        $language = \in_array('typescript', \array_keys($allDeps), true) ? 'TypeScript' : 'JavaScript';

        /** @var array<string, string> $engines */
        $engines = \is_array($data['engines'] ?? null) ? $data['engines'] : [];
        $version = isset($engines['node']) ? VersionHelper::clean($engines['node']) : '';

        $framework = 'none';
        $frameworkVersion = '';

        foreach (self::FRAMEWORK_MAP as $pkg => $name) {
            if (isset($allDeps[$pkg])) {
                if ($pkg === 'angular' && isset($allDeps['@angular/core'])) {
                    continue;
                }
                $framework = $name;
                $frameworkVersion = VersionHelper::clean($allDeps[$pkg]);
                break;
            }
        }

        return [new DetectedStack(language: $language, framework: $framework, version: $version, frameworkVersion: $frameworkVersion)];
    }

    public function extractDependencies(array $manifestContents): array
    {
        $packageJson = $manifestContents['package.json'] ?? null;
        if ($packageJson === null) {
            return [];
        }

        $data = \json_decode($packageJson, true);
        if (!\is_array($data)) {
            return [];
        }

        $deps = [];

        /** @var array<string, string> $runtimeDeps */
        $runtimeDeps = \is_array($data['dependencies'] ?? null) ? $data['dependencies'] : [];
        foreach ($runtimeDeps as $name => $version) {
            $deps[] = new DetectedDependency(
                name: $name,
                currentVersion: VersionHelper::clean($version),
                packageManager: PackageManager::Npm,
                type: DependencyType::Runtime,
                repositoryUrl: \sprintf('https://www.npmjs.com/package/%s', $name),
            );
        }

        /** @var array<string, string> $devDeps */
        $devDeps = \is_array($data['devDependencies'] ?? null) ? $data['devDependencies'] : [];
        foreach ($devDeps as $name => $version) {
            $deps[] = new DetectedDependency(
                name: $name,
                currentVersion: VersionHelper::clean($version),
                packageManager: PackageManager::Npm,
                type: DependencyType::Dev,
                repositoryUrl: \sprintf('https://www.npmjs.com/package/%s', $name),
            );
        }

        return $deps;
    }

    public function enrichStackVersions(array $stacks, array $lockVersions): array
    {
        return \array_map(
            function (DetectedStack $stack) use ($lockVersions): DetectedStack {
                if ($stack->language !== 'TypeScript' && $stack->language !== 'JavaScript') {
                    return $stack;
                }

                $enrichedFrameworkVersion = $stack->frameworkVersion;

                $frameworkPkg = self::FRAMEWORK_LOCK_PACKAGES[$stack->framework] ?? null;
                if ($frameworkPkg !== null && isset($lockVersions[$frameworkPkg])) {
                    $enrichedFrameworkVersion = $lockVersions[$frameworkPkg];
                } elseif ($stack->framework === 'Angular') {
                    if (isset($lockVersions['@angular/core'])) {
                        $enrichedFrameworkVersion = $lockVersions['@angular/core'];
                    } elseif (isset($lockVersions['angular'])) {
                        $enrichedFrameworkVersion = $lockVersions['angular'];
                    }
                }

                $enrichedVersion = $stack->version;
                if (isset($lockVersions['typescript'])) {
                    $enrichedVersion = $lockVersions['typescript'];
                } elseif ($stack->language === 'JavaScript' && isset($lockVersions['node'])) {
                    $enrichedVersion = $lockVersions['node'];
                }

                return new DetectedStack(
                    language: $stack->language,
                    framework: $stack->framework,
                    version: $enrichedVersion,
                    frameworkVersion: $enrichedFrameworkVersion,
                );
            },
            $stacks,
        );
    }

    public function enrichDependencyVersions(array $dependencies, array $lockVersions): array
    {
        return \array_map(
            static function (DetectedDependency $dep) use ($lockVersions): DetectedDependency {
                if ($dep->packageManager !== PackageManager::Npm) {
                    return $dep;
                }

                $locked = $lockVersions[$dep->name] ?? null;
                if ($locked === null) {
                    return $dep;
                }

                return new DetectedDependency(
                    name: $dep->name,
                    currentVersion: $locked,
                    packageManager: $dep->packageManager,
                    type: $dep->type,
                    repositoryUrl: $dep->repositoryUrl,
                );
            },
            $dependencies,
        );
    }
}

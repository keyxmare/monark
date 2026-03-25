<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Scanner;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Port\ProjectScannerInterface;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ProjectScanner implements ProjectScannerInterface
{
    private const array MANIFEST_FILES = [
        'composer.json',
        'composer.lock',
        'package.json',
        'requirements.txt',
        'pyproject.toml',
        'go.mod',
        'Cargo.toml',
        'Gemfile',
        'Dockerfile',
    ];

    public function __construct(
        private GitProviderFactoryInterface $gitProviderFactory,
        private LoggerInterface $logger = new NullLogger(),
    ) {
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

        /** @var list<DetectedStack|null> $stacks */
        $stacks = [];
        /** @var list<DetectedDependency> $dependencies */
        $dependencies = [];

        $rootNpmLockVersions = $this->resolveNpmLockVersions($gitProvider, $provider, $externalId, '', $ref);

        foreach ($searchPaths as $basePath) {
            $prefix = $basePath === '' ? '' : $basePath . '/';

            $composerJson = $gitProvider->getFileContent($provider, $externalId, $prefix . 'composer.json', $ref);
            if ($composerJson !== null) {
                $data = \json_decode($composerJson, true);
                if (\is_array($data)) {
                    /** @var array<string, mixed> $data */
                    $stacks[] = $this->detectPhpStack($data);
                    \array_push($dependencies, ...$this->extractComposerDeps($data));
                }
            }

            $composerLock = $gitProvider->getFileContent($provider, $externalId, $prefix . 'composer.lock', $ref);
            if ($composerLock !== null && $composerJson !== null) {
                $lockData = \json_decode($composerLock, true);
                if (\is_array($lockData)) {
                    /** @var array<string, mixed> $lockData */
                    $dependencies = $this->enrichComposerVersions($dependencies, $lockData);
                    $dependencies = $this->enrichComposerUrls($dependencies, $lockData);
                    $stacks = $this->enrichPhpStackVersions($stacks, $lockData);
                }
            }

            $packageJson = $gitProvider->getFileContent($provider, $externalId, $prefix . 'package.json', $ref);
            if ($packageJson !== null) {
                $data = \json_decode($packageJson, true);
                if (\is_array($data)) {
                    /** @var array<string, mixed> $data */
                    $stacks[] = $this->detectJsStack($data);
                    \array_push($dependencies, ...$this->extractNpmDeps($data));
                }

                $localNpmLockVersions = $prefix !== '' ? $this->resolveNpmLockVersions($gitProvider, $provider, $externalId, $prefix, $ref) : [];
                $npmLockVersions = \array_merge($rootNpmLockVersions, $localNpmLockVersions);
                if ($npmLockVersions !== []) {
                    $dependencies = $this->enrichNpmVersions($dependencies, $npmLockVersions);
                    $stacks = $this->enrichJsStackVersions($stacks, $npmLockVersions);
                }
            }

            $requirementsTxt = $gitProvider->getFileContent($provider, $externalId, $prefix . 'requirements.txt', $ref);
            if ($requirementsTxt !== null) {
                $stacks[] = $this->detectPythonStack($requirementsTxt);
                \array_push($dependencies, ...$this->extractPipDeps($requirementsTxt));
            }

            $pyprojectToml = $gitProvider->getFileContent($provider, $externalId, $prefix . 'pyproject.toml', $ref);
            if ($pyprojectToml !== null && $requirementsTxt === null) {
                $stacks[] = $this->detectPyprojectStack($pyprojectToml);
            }

            $goMod = $gitProvider->getFileContent($provider, $externalId, $prefix . 'go.mod', $ref);
            if ($goMod !== null) {
                $stacks[] = $this->detectGoStack($goMod);
            }

            $cargoToml = $gitProvider->getFileContent($provider, $externalId, $prefix . 'Cargo.toml', $ref);
            if ($cargoToml !== null) {
                $stacks[] = $this->detectRustStack($cargoToml);
            }

            $gemfile = $gitProvider->getFileContent($provider, $externalId, $prefix . 'Gemfile', $ref);
            if ($gemfile !== null) {
                $stacks[] = $this->detectRubyStack($gemfile);
            }

            $dockerfile = $gitProvider->getFileContent($provider, $externalId, $prefix . 'Dockerfile', $ref);
            if ($dockerfile !== null) {
                $dockerStack = $this->detectDockerStack($dockerfile);
                if ($dockerStack !== null) {
                    $stacks[] = $dockerStack;
                }
            }
        }

        return new ScanResult(
            stacks: $this->deduplicateStacks(\array_values(\array_filter($stacks))),
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
                if ($subEntry['type'] === 'blob' && \in_array($subEntry['name'], self::MANIFEST_FILES, true)) {
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

    private const array FRAMEWORK_PACKAGES = [
        'Symfony' => 'symfony/framework-bundle',
        'Laravel' => 'laravel/framework',
        'Slim' => 'slim/slim',
        'CakePHP' => 'cakephp/cakephp',
        'Yii2' => 'yiisoft/yii2',
    ];

    /**
     * @param list<DetectedStack|null> $stacks
     * @param array<string, mixed> $lockData
     * @return list<DetectedStack|null>
     */
    private function enrichPhpStackVersions(array $stacks, array $lockData): array
    {
        /** @var list<array{name?: string, version?: string}> $packages */
        $packages = \array_merge(
            \is_array($lockData['packages'] ?? null) ? $lockData['packages'] : [],
            \is_array($lockData['packages-dev'] ?? null) ? $lockData['packages-dev'] : [],
        );

        $lockVersions = [];
        foreach ($packages as $pkg) {
            if (isset($pkg['name'])) {
                $lockVersions[$pkg['name']] = \ltrim($pkg['version'] ?? '', 'v');
            }
        }

        /** @var array<string, string>|null $platform */
        $platform = \is_array($lockData['platform'] ?? null) ? $lockData['platform'] : null;
        /** @var array<string, string>|null $platformOverrides */
        $platformOverrides = \is_array($lockData['platform-overrides'] ?? null) ? $lockData['platform-overrides'] : null;
        $phpVersion = $platform['php'] ?? $platformOverrides['php'] ?? null;

        return \array_map(
            static function (?DetectedStack $stack) use ($lockVersions, $phpVersion): ?DetectedStack {
                if ($stack === null || $stack->language !== 'PHP') {
                    return $stack;
                }

                $enrichedPhpVersion = $phpVersion !== null ? \ltrim($phpVersion, 'v') : $stack->version;

                $enrichedFrameworkVersion = $stack->frameworkVersion;
                $frameworkPkg = self::FRAMEWORK_PACKAGES[$stack->framework] ?? null;
                if ($frameworkPkg !== null && isset($lockVersions[$frameworkPkg])) {
                    $enrichedFrameworkVersion = $lockVersions[$frameworkPkg];
                } elseif ($stack->framework !== 'none') {
                    $prefix = match ($stack->framework) {
                        'Symfony' => 'symfony/',
                        'Laravel' => 'laravel/',
                        default => null,
                    };
                    if ($prefix !== null) {
                        foreach ($lockVersions as $pkgName => $pkgVer) {
                            if (\str_starts_with($pkgName, $prefix)) {
                                $enrichedFrameworkVersion = $pkgVer;
                                break;
                            }
                        }
                    }
                }

                return new DetectedStack(
                    language: $stack->language,
                    framework: $stack->framework,
                    version: $enrichedPhpVersion,
                    frameworkVersion: $enrichedFrameworkVersion,
                );
            },
            $stacks,
        );
    }

    /** @param array<string, mixed> $data */
    private function detectPhpStack(array $data): DetectedStack
    {
        /** @var array<string, string> $require */
        $require = \is_array($data['require'] ?? null) ? $data['require'] : [];
        $phpVersion = $this->cleanVersion((string) ($require['php'] ?? ''));
        $framework = 'none';
        $frameworkVersion = '';

        $flipped = \array_flip(self::FRAMEWORK_PACKAGES);

        foreach ($flipped as $pkg => $name) {
            if (isset($require[$pkg])) {
                $framework = $name;
                $frameworkVersion = $this->cleanVersion((string) $require[$pkg]);
                break;
            }
        }

        if ($framework === 'none') {
            foreach ($require as $pkg => $ver) {
                if (\str_starts_with($pkg, 'symfony/')) {
                    $framework = 'Symfony';
                    $frameworkVersion = $this->cleanVersion($ver);
                    break;
                }
                if (\str_starts_with($pkg, 'laravel/')) {
                    $framework = 'Laravel';
                    $frameworkVersion = $this->cleanVersion($ver);
                    break;
                }
            }
        }

        return new DetectedStack(language: 'PHP', framework: $framework, version: $phpVersion, frameworkVersion: $frameworkVersion);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<DetectedDependency>
     */
    private function extractComposerDeps(array $data): array
    {
        $deps = [];

        /** @var array<string, string> $require */
        $require = \is_array($data['require'] ?? null) ? $data['require'] : [];
        foreach ($require as $name => $version) {
            if ($name === 'php' || \str_starts_with($name, 'ext-')) {
                continue;
            }
            $deps[] = new DetectedDependency(
                name: $name,
                currentVersion: $this->cleanVersion($version),
                packageManager: PackageManager::Composer,
                type: DependencyType::Runtime,
            );
        }

        /** @var array<string, string> $requireDev */
        $requireDev = \is_array($data['require-dev'] ?? null) ? $data['require-dev'] : [];
        foreach ($requireDev as $name => $version) {
            $deps[] = new DetectedDependency(
                name: $name,
                currentVersion: $this->cleanVersion($version),
                packageManager: PackageManager::Composer,
                type: DependencyType::Dev,
            );
        }

        return $deps;
    }

    /**
     * @param list<DetectedDependency> $dependencies
     * @param array<string, mixed> $lockData
     * @return list<DetectedDependency>
     */
    private function enrichComposerVersions(array $dependencies, array $lockData): array
    {
        /** @var list<array{name?: string, version?: string}> $packages */
        $packages = \array_merge(
            \is_array($lockData['packages'] ?? null) ? $lockData['packages'] : [],
            \is_array($lockData['packages-dev'] ?? null) ? $lockData['packages-dev'] : [],
        );

        $lockVersions = [];
        foreach ($packages as $pkg) {
            if (isset($pkg['name'])) {
                $lockVersions[$pkg['name']] = \ltrim($pkg['version'] ?? '', 'v');
            }
        }

        return \array_map(
            static function (DetectedDependency $dep) use ($lockVersions): DetectedDependency {
                if ($dep->packageManager !== PackageManager::Composer) {
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
                );
            },
            $dependencies,
        );
    }

    /**
     * @param list<DetectedDependency> $dependencies
     * @param array<string, mixed> $lockData
     * @return list<DetectedDependency>
     */
    private function enrichComposerUrls(array $dependencies, array $lockData): array
    {
        /** @var list<array{name?: string, source?: array{url?: string}, homepage?: string}> $packages */
        $packages = \array_merge(
            \is_array($lockData['packages'] ?? null) ? $lockData['packages'] : [],
            \is_array($lockData['packages-dev'] ?? null) ? $lockData['packages-dev'] : [],
        );

        $lockUrls = [];
        foreach ($packages as $pkg) {
            $url = ($pkg['source']['url'] ?? null) ?? ($pkg['homepage'] ?? null);
            if ($url !== null && isset($pkg['name'])) {
                $lockUrls[$pkg['name']] = \rtrim(\str_replace('.git', '', $url), '/');
            }
        }

        return \array_map(
            static function (DetectedDependency $dep) use ($lockUrls): DetectedDependency {
                if ($dep->packageManager !== PackageManager::Composer || $dep->repositoryUrl !== null) {
                    return $dep;
                }

                $url = $lockUrls[$dep->name] ?? null;
                if ($url === null) {
                    return $dep;
                }

                return new DetectedDependency(
                    name: $dep->name,
                    currentVersion: $dep->currentVersion,
                    packageManager: $dep->packageManager,
                    type: $dep->type,
                    repositoryUrl: $url,
                );
            },
            $dependencies,
        );
    }

    private function resolveNpmUrl(string $name): string
    {
        return \sprintf('https://www.npmjs.com/package/%s', $name);
    }

    /** @param array<string, mixed> $data */
    private function detectJsStack(array $data): DetectedStack
    {
        /** @var array<string, string> $deps */
        $deps = \is_array($data['dependencies'] ?? null) ? $data['dependencies'] : [];
        /** @var array<string, string> $devDeps */
        $devDeps = \is_array($data['devDependencies'] ?? null) ? $data['devDependencies'] : [];
        /** @var array<string, string> $allDeps */
        $allDeps = \array_merge($deps, $devDeps);
        $allDepsKeys = \array_keys($allDeps);

        $language = \in_array('typescript', $allDepsKeys, true) ? 'TypeScript' : 'JavaScript';
        $framework = 'none';
        $frameworkVersion = '';

        /** @var array<string, string> $engines */
        $engines = \is_array($data['engines'] ?? null) ? $data['engines'] : [];
        $version = isset($engines['node']) ? $this->cleanVersion($engines['node']) : '';

        $frameworkMap = [
            'nuxt' => 'Nuxt',
            '@nuxt/core' => 'Nuxt',
            'next' => 'Next.js',
            'vue' => 'Vue',
            'react' => 'React',
            '@angular/core' => 'Angular',
            'svelte' => 'Svelte',
            'astro' => 'Astro',
            'remix' => 'Remix',
        ];

        foreach ($frameworkMap as $pkg => $name) {
            if (isset($allDeps[$pkg])) {
                $framework = $name;
                $frameworkVersion = $this->cleanVersion($allDeps[$pkg]);
                break;
            }
        }

        return new DetectedStack(language: $language, framework: $framework, version: $version, frameworkVersion: $frameworkVersion);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<DetectedDependency>
     */
    private function extractNpmDeps(array $data): array
    {
        $deps = [];

        /** @var array<string, string> $runtimeDeps */
        $runtimeDeps = \is_array($data['dependencies'] ?? null) ? $data['dependencies'] : [];
        foreach ($runtimeDeps as $name => $version) {
            $deps[] = new DetectedDependency(
                name: $name,
                currentVersion: $this->cleanVersion($version),
                packageManager: PackageManager::Npm,
                type: DependencyType::Runtime,
                repositoryUrl: $this->resolveNpmUrl($name),
            );
        }

        /** @var array<string, string> $devDeps */
        $devDeps = \is_array($data['devDependencies'] ?? null) ? $data['devDependencies'] : [];
        foreach ($devDeps as $name => $version) {
            $deps[] = new DetectedDependency(
                name: $name,
                currentVersion: $this->cleanVersion($version),
                packageManager: PackageManager::Npm,
                type: DependencyType::Dev,
                repositoryUrl: $this->resolveNpmUrl($name),
            );
        }

        return $deps;
    }

    private function detectPythonStack(string $content): DetectedStack
    {
        $framework = 'none';
        $lower = \strtolower($content);

        if (\str_contains($lower, 'django')) {
            $framework = 'Django';
        } elseif (\str_contains($lower, 'fastapi')) {
            $framework = 'FastAPI';
        } elseif (\str_contains($lower, 'flask')) {
            $framework = 'Flask';
        }

        return new DetectedStack(language: 'Python', framework: $framework, version: '', frameworkVersion: '');
    }

    /** @return list<DetectedDependency> */
    private function extractPipDeps(string $content): array
    {
        $deps = [];
        $lines = \explode("\n", $content);

        foreach ($lines as $line) {
            $line = \trim($line);
            if ($line === '' || \str_starts_with($line, '#') || \str_starts_with($line, '-')) {
                continue;
            }

            if (\preg_match('/^([a-zA-Z0-9_.-]+)\s*(?:[=<>!~]+\s*(.+))?$/', $line, $m)) {
                $deps[] = new DetectedDependency(
                    name: $m[1],
                    currentVersion: isset($m[2]) ? \trim($m[2]) : '*',
                    packageManager: PackageManager::Pip,
                    type: DependencyType::Runtime,
                    repositoryUrl: \sprintf('https://pypi.org/project/%s/', $m[1]),
                );
            }
        }

        return $deps;
    }

    private function detectPyprojectStack(string $content): DetectedStack
    {
        $framework = 'none';
        $lower = \strtolower($content);

        if (\str_contains($lower, 'django')) {
            $framework = 'Django';
        } elseif (\str_contains($lower, 'fastapi')) {
            $framework = 'FastAPI';
        } elseif (\str_contains($lower, 'flask')) {
            $framework = 'Flask';
        }

        $version = '';
        if (\preg_match('/requires-python\s*=\s*"([^"]+)"/', $content, $m)) {
            $version = $m[1];
        }

        return new DetectedStack(language: 'Python', framework: $framework, version: $version, frameworkVersion: '');
    }

    private function detectGoStack(string $content): DetectedStack
    {
        $version = '';
        if (\preg_match('/^go\s+([\d.]+)/m', $content, $m)) {
            $version = $m[1];
        }

        $framework = 'none';
        if (\str_contains($content, 'github.com/gin-gonic/gin')) {
            $framework = 'Gin';
        } elseif (\str_contains($content, 'github.com/gofiber/fiber')) {
            $framework = 'Fiber';
        } elseif (\str_contains($content, 'github.com/labstack/echo')) {
            $framework = 'Echo';
        }

        return new DetectedStack(language: 'Go', framework: $framework, version: $version, frameworkVersion: '');
    }

    private function detectRustStack(string $content): DetectedStack
    {
        $version = '';
        if (\preg_match('/\[package\].*?version\s*=\s*"([^"]+)"/s', $content, $m)) {
            $version = $m[1];
        }

        $framework = 'none';
        if (\str_contains($content, 'actix-web')) {
            $framework = 'Actix';
        } elseif (\str_contains($content, 'axum')) {
            $framework = 'Axum';
        } elseif (\str_contains($content, 'rocket')) {
            $framework = 'Rocket';
        }

        return new DetectedStack(language: 'Rust', framework: $framework, version: $version, frameworkVersion: '');
    }

    private function detectRubyStack(string $content): DetectedStack
    {
        $framework = 'none';
        if (\str_contains($content, "'rails'") || \str_contains($content, '"rails"')) {
            $framework = 'Rails';
        } elseif (\str_contains($content, "'sinatra'") || \str_contains($content, '"sinatra"')) {
            $framework = 'Sinatra';
        }

        $version = '';
        if (\preg_match("/ruby ['\"]([^'\"]+)['\"]/", $content, $m)) {
            $version = $m[1];
        }

        return new DetectedStack(language: 'Ruby', framework: $framework, version: $version, frameworkVersion: '');
    }

    private function detectDockerStack(string $content): ?DetectedStack
    {
        if (!\preg_match('/^FROM\s+([^\s]+)/im', $content, $m)) {
            return null;
        }

        $image = \strtolower(\explode(':', $m[1])[0]);
        $image = \explode('/', $image);
        $base = \end($image);

        $langMap = [
            'php' => 'PHP',
            'node' => 'Node.js',
            'python' => 'Python',
            'golang' => 'Go',
            'rust' => 'Rust',
            'ruby' => 'Ruby',
            'openjdk' => 'Java',
            'eclipse-temurin' => 'Java',
            'amazoncorretto' => 'Java',
            'dotnet' => 'C#',
        ];

        $language = $langMap[$base] ?? null;
        if ($language === null) {
            return null;
        }

        $version = '';
        if (\preg_match('/^FROM\s+[^:]+:(\d+(?:\.\d+)*)/im', $content, $vm)) {
            $version = $vm[1];
        }

        return new DetectedStack(language: $language, framework: 'none', version: $version, frameworkVersion: '');
    }

    private function cleanVersion(string $version): string
    {
        return \ltrim(\trim($version), '^~>=<! ');
    }

    /**
     * @return array<string, string>
     */
    private function resolveNpmLockVersions(
        GitProviderInterface $gitProvider,
        \App\Catalog\Domain\Model\Provider $provider,
        string $externalId,
        string $prefix,
        string $ref,
    ): array {
        $pnpmLock = $gitProvider->getFileContent($provider, $externalId, $prefix . 'pnpm-lock.yaml', $ref);
        if ($pnpmLock !== null) {
            return $this->parsePnpmLock($pnpmLock);
        }

        $npmLock = $gitProvider->getFileContent($provider, $externalId, $prefix . 'package-lock.json', $ref);
        if ($npmLock !== null) {
            $data = \json_decode($npmLock, true);
            if (\is_array($data)) {
                return $this->parseNpmLock($data);
            }
        }

        $yarnLock = $gitProvider->getFileContent($provider, $externalId, $prefix . 'yarn.lock', $ref);
        if ($yarnLock !== null) {
            return $this->parseYarnLock($yarnLock);
        }

        return [];
    }

    /** @return array<string, string> */
    private function parsePnpmLock(string $content): array
    {
        $versions = [];

        if (\preg_match_all('/^\s+([\'"]?)([a-z@][a-z0-9\/@._-]*)\1:\s*$/m', $content, $pkgMatches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            foreach ($pkgMatches as $match) {
                $pkgName = $match[2][0];
                $offset = $match[0][1];
                $rest = \substr($content, $offset, 500);
                if (\preg_match('/version:\s*[\'"]?(\d+(?:\.\d+)*)/m', $rest, $vm)) {
                    $versions[$pkgName] = \ltrim($vm[1], 'v');
                }
            }
        }

        return $versions;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function parseNpmLock(array $data): array
    {
        $versions = [];

        /** @var array<string, array{version?: string}> $packages */
        $packages = \is_array($data['packages'] ?? null) ? $data['packages'] : [];
        foreach ($packages as $path => $info) {
            if ($path === '' || !\str_starts_with($path, 'node_modules/')) {
                continue;
            }
            $name = \substr($path, \strlen('node_modules/'));
            if (isset($info['version'])) {
                $versions[$name] = \ltrim($info['version'], 'v');
            }
        }

        return $versions;
    }

    /** @return array<string, string> */
    private function parseYarnLock(string $content): array
    {
        $versions = [];

        if (\preg_match_all('/^"?(@?[a-z][a-z0-9\/@._-]*)@(?:npm:)?[^":\n]+(?:,\s*"?@?[a-z][a-z0-9\/@._-]*@(?:npm:)?[^":\n]+)*"?:\s*$/m', $content, $pkgMatches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            foreach ($pkgMatches as $match) {
                $pkgName = $match[1][0];
                $offset = $match[0][1];
                $rest = \substr($content, $offset, 300);
                if (\preg_match('/^\s+version:\s*["\']?(\d+(?:\.\d+)*)/m', $rest, $vm)) {
                    if (!isset($versions[$pkgName])) {
                        $versions[$pkgName] = $vm[1];
                    }
                }
            }
        }

        return $versions;
    }

    /**
     * @param list<DetectedDependency> $dependencies
     * @param array<string, string> $lockVersions
     * @return list<DetectedDependency>
     */
    private function enrichNpmVersions(array $dependencies, array $lockVersions): array
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

    private const array JS_FRAMEWORK_PACKAGES = [
        'Vue' => 'vue',
        'Nuxt' => 'nuxt',
        'React' => 'react',
        'Next.js' => 'next',
        'Angular' => '@angular/core',
        'Svelte' => 'svelte',
        'Astro' => 'astro',
        'Remix' => 'remix',
    ];

    /**
     * @param list<DetectedStack|null> $stacks
     * @param array<string, string> $lockVersions
     * @return list<DetectedStack|null>
     */
    private function enrichJsStackVersions(array $stacks, array $lockVersions): array
    {
        return \array_map(
            static function (?DetectedStack $stack) use ($lockVersions): ?DetectedStack {
                if ($stack === null || ($stack->language !== 'TypeScript' && $stack->language !== 'JavaScript')) {
                    return $stack;
                }

                $enrichedFrameworkVersion = $stack->frameworkVersion;
                $frameworkPkg = self::JS_FRAMEWORK_PACKAGES[$stack->framework] ?? null;
                if ($frameworkPkg !== null && isset($lockVersions[$frameworkPkg])) {
                    $enrichedFrameworkVersion = $lockVersions[$frameworkPkg];
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

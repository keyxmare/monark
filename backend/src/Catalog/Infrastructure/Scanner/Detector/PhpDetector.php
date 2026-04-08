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
class PhpDetector implements StackDetectorInterface
{
    private const array FRAMEWORK_PACKAGES = [
        'Symfony' => 'symfony/framework-bundle',
        'Laravel' => 'laravel/framework',
        'Slim' => 'slim/slim',
        'CakePHP' => 'cakephp/cakephp',
        'Yii2' => 'yiisoft/yii2',
    ];

    public function supportedManifests(): array
    {
        return ['composer.json', 'composer.lock'];
    }

    public function detect(array $manifestContents): array
    {
        $composerJson = $manifestContents['composer.json'] ?? null;
        if ($composerJson === null) {
            return [];
        }

        $data = \json_decode($composerJson, true);
        if (!\is_array($data)) {
            return [];
        }

        /** @var array<string, string> $require */
        $require = \is_array($data['require'] ?? null) ? $data['require'] : [];
        $phpVersion = VersionHelper::clean((string) ($require['php'] ?? ''));
        $framework = 'none';
        $frameworkVersion = '';

        $flipped = \array_flip(self::FRAMEWORK_PACKAGES);

        foreach ($flipped as $pkg => $name) {
            if (isset($require[$pkg])) {
                $framework = $name;
                $frameworkVersion = VersionHelper::clean((string) $require[$pkg]);
                break;
            }
        }

        if ($framework === 'none') {
            foreach ($require as $pkg => $ver) {
                if (\str_starts_with($pkg, 'symfony/')) {
                    $framework = 'Symfony';
                    $frameworkVersion = VersionHelper::clean($ver);
                    break;
                }
                if (\str_starts_with($pkg, 'laravel/')) {
                    $framework = 'Laravel';
                    $frameworkVersion = VersionHelper::clean($ver);
                    break;
                }
            }
        }

        return [new DetectedStack(language: 'PHP', framework: $framework, version: $phpVersion, frameworkVersion: $frameworkVersion)];
    }

    public function extractDependencies(array $manifestContents): array
    {
        $composerJson = $manifestContents['composer.json'] ?? null;
        if ($composerJson === null) {
            return [];
        }

        $data = \json_decode($composerJson, true);
        if (!\is_array($data)) {
            return [];
        }

        $deps = [];

        /** @var array<string, string> $require */
        $require = \is_array($data['require'] ?? null) ? $data['require'] : [];
        foreach ($require as $name => $version) {
            if ($name === 'php' || \str_starts_with($name, 'ext-')) {
                continue;
            }
            $deps[] = new DetectedDependency(
                name: $name,
                currentVersion: VersionHelper::clean($version),
                packageManager: PackageManager::Composer,
                type: DependencyType::Runtime,
            );
        }

        /** @var array<string, string> $requireDev */
        $requireDev = \is_array($data['require-dev'] ?? null) ? $data['require-dev'] : [];
        foreach ($requireDev as $name => $version) {
            $deps[] = new DetectedDependency(
                name: $name,
                currentVersion: VersionHelper::clean($version),
                packageManager: PackageManager::Composer,
                type: DependencyType::Dev,
            );
        }

        return $deps;
    }

    public function enrichStackVersions(array $stacks, array $lockVersions): array
    {
        return \array_map(
            function (DetectedStack $stack) use ($lockVersions): DetectedStack {
                if ($stack->language !== 'PHP') {
                    return $stack;
                }

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
                    version: $stack->version,
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
                    repositoryUrl: $dep->repositoryUrl,
                );
            },
            $dependencies,
        );
    }

    /**
     * @param list<DetectedDependency> $dependencies
     * @param array<string, string> $manifestContents
     * @return list<DetectedDependency>
     */
    public function enrichDependencyUrls(array $dependencies, array $manifestContents): array
    {
        $composerLock = $manifestContents['composer.lock'] ?? null;
        if ($composerLock === null) {
            return $dependencies;
        }

        $lockData = \json_decode($composerLock, true);
        if (!\is_array($lockData)) {
            return $dependencies;
        }

        /** @var array<string, mixed> $lockData */
        $lockUrls = VersionHelper::parseComposerLockUrls($lockData);

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
}

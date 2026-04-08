<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\PhpDetector;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

describe('PhpDetector', function () {
    describe('supportedManifests', function () {
        it('returns exactly composer.json and composer.lock', function () {
            $detector = new PhpDetector();
            expect($detector->supportedManifests())->toBe(['composer.json', 'composer.lock']);
        });
    });

    describe('detect', function () {
        it('detects PHP + Symfony from composer.json', function () {
            $composerJson = \json_encode([
                'require' => [
                    'php' => '>=8.4',
                    'symfony/framework-bundle' => '^8.0',
                ],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks)->toHaveCount(1);
            expect($stacks[0]->language)->toBe('PHP');
            expect($stacks[0]->framework)->toBe('Symfony');
            expect($stacks[0]->version)->toBe('8.4');
            expect($stacks[0]->frameworkVersion)->toBe('8.0');
        });

        it('detects Laravel framework', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=8.3', 'laravel/framework' => '^11.0'],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->framework)->toBe('Laravel');
            expect($stacks[0]->frameworkVersion)->toBe('11.0');
        });

        it('detects Slim framework', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=8.1', 'slim/slim' => '^4.0'],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->framework)->toBe('Slim');
            expect($stacks[0]->frameworkVersion)->toBe('4.0');
        });

        it('detects CakePHP framework', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=8.1', 'cakephp/cakephp' => '^5.0'],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->framework)->toBe('CakePHP');
            expect($stacks[0]->frameworkVersion)->toBe('5.0');
        });

        it('detects Yii2 framework', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=7.4', 'yiisoft/yii2' => '^2.0'],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->framework)->toBe('Yii2');
            expect($stacks[0]->frameworkVersion)->toBe('2.0');
        });

        it('falls back to symfony/ prefix detection', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=8.3', 'symfony/http-foundation' => '^7.0'],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->framework)->toBe('Symfony');
            expect($stacks[0]->frameworkVersion)->toBe('7.0');
        });

        it('falls back to laravel/ prefix detection', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=8.2', 'laravel/sanctum' => '^4.0'],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->framework)->toBe('Laravel');
            expect($stacks[0]->frameworkVersion)->toBe('4.0');
        });

        it('returns none framework when no known framework found', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=8.4', 'monolog/monolog' => '^3.0'],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->framework)->toBe('none');
            expect($stacks[0]->frameworkVersion)->toBe('');
        });

        it('returns empty when no composer.json provided', function () {
            $detector = new PhpDetector();
            expect($detector->detect([]))->toBe([]);
        });

        it('returns empty when composer.json is null in array', function () {
            $detector = new PhpDetector();
            expect($detector->detect(['composer.lock' => '{}']))->toBe([]);
        });

        it('returns empty for invalid JSON', function () {
            $detector = new PhpDetector();
            expect($detector->detect(['composer.json' => 'not json']))->toBe([]);
        });

        it('handles missing require key', function () {
            $composerJson = \json_encode(['name' => 'my/app']);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks)->toHaveCount(1);
            expect($stacks[0]->language)->toBe('PHP');
            expect($stacks[0]->version)->toBe('');
            expect($stacks[0]->framework)->toBe('none');
        });

        it('handles require with no php key', function () {
            $composerJson = \json_encode([
                'require' => ['doctrine/orm' => '^3.0'],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->version)->toBe('');
        });

        it('gives priority to FRAMEWORK_PACKAGES over prefix fallback', function () {
            $composerJson = \json_encode([
                'require' => [
                    'symfony/framework-bundle' => '^8.0',
                    'laravel/framework' => '^11.0',
                ],
            ]);

            $detector = new PhpDetector();
            $stacks = $detector->detect(['composer.json' => $composerJson]);

            expect($stacks[0]->framework)->toBe('Symfony');
        });
    });

    describe('extractDependencies', function () {
        it('extracts runtime and dev dependencies filtering php and ext-', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=8.4', 'ext-json' => '*', 'doctrine/orm' => '^3.0'],
                'require-dev' => ['pestphp/pest' => '^4.0'],
            ]);

            $detector = new PhpDetector();
            $deps = $detector->extractDependencies(['composer.json' => $composerJson]);

            expect($deps)->toHaveCount(2);
            expect($deps[0]->name)->toBe('doctrine/orm');
            expect($deps[0]->currentVersion)->toBe('3.0');
            expect($deps[0]->packageManager)->toBe(PackageManager::Composer);
            expect($deps[0]->type)->toBe(DependencyType::Runtime);
            expect($deps[1]->name)->toBe('pestphp/pest');
            expect($deps[1]->currentVersion)->toBe('4.0');
            expect($deps[1]->type)->toBe(DependencyType::Dev);
        });

        it('returns empty when no composer.json', function () {
            $detector = new PhpDetector();
            expect($detector->extractDependencies([]))->toBe([]);
        });

        it('returns empty for invalid JSON', function () {
            $detector = new PhpDetector();
            expect($detector->extractDependencies(['composer.json' => '{invalid}']))->toBe([]);
        });

        it('returns empty when require and require-dev are missing', function () {
            $detector = new PhpDetector();
            $deps = $detector->extractDependencies(['composer.json' => \json_encode(['name' => 'x'])]);
            expect($deps)->toBe([]);
        });

        it('filters multiple ext- packages', function () {
            $composerJson = \json_encode([
                'require' => ['php' => '>=8.4', 'ext-json' => '*', 'ext-mbstring' => '*', 'ext-pdo' => '*'],
            ]);

            $detector = new PhpDetector();
            $deps = $detector->extractDependencies(['composer.json' => $composerJson]);
            expect($deps)->toBe([]);
        });

        it('handles non-array require value', function () {
            $detector = new PhpDetector();
            $deps = $detector->extractDependencies(['composer.json' => \json_encode(['require' => 'invalid'])]);
            expect($deps)->toBe([]);
        });
    });

    describe('enrichStackVersions', function () {
        it('enriches Symfony framework version from lock', function () {
            $stacks = [new DetectedStack('PHP', 'Symfony', '8.3', '7.2')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['symfony/framework-bundle' => '7.2.5']);

            expect($enriched[0]->frameworkVersion)->toBe('7.2.5');
            expect($enriched[0]->version)->toBe('8.3');
            expect($enriched[0]->language)->toBe('PHP');
        });

        it('enriches Laravel framework version from lock', function () {
            $stacks = [new DetectedStack('PHP', 'Laravel', '8.3', '11.0')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['laravel/framework' => '11.5.2']);

            expect($enriched[0]->frameworkVersion)->toBe('11.5.2');
        });

        it('falls back to symfony/ prefix when framework-bundle not in lock', function () {
            $stacks = [new DetectedStack('PHP', 'Symfony', '8.3', '7.0')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['symfony/http-kernel' => '7.2.1']);

            expect($enriched[0]->frameworkVersion)->toBe('7.2.1');
        });

        it('falls back to laravel/ prefix when laravel/framework not in lock', function () {
            $stacks = [new DetectedStack('PHP', 'Laravel', '8.3', '11.0')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['laravel/sanctum' => '4.1.0']);

            expect($enriched[0]->frameworkVersion)->toBe('4.1.0');
        });

        it('passes through non-PHP stacks unchanged', function () {
            $stacks = [new DetectedStack('JavaScript', 'Vue', '', '3.5.0')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['vue' => '3.5.13']);

            expect($enriched[0])->toBe($stacks[0]);
        });

        it('keeps original version for none framework', function () {
            $stacks = [new DetectedStack('PHP', 'none', '8.4', '')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['doctrine/orm' => '3.4.2']);

            expect($enriched[0]->frameworkVersion)->toBe('');
            expect($enriched[0]->framework)->toBe('none');
        });

        it('keeps original version for unknown framework without prefix match', function () {
            $stacks = [new DetectedStack('PHP', 'CakePHP', '8.1', '5.0')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['cakephp/cakephp' => '5.1.3']);

            expect($enriched[0]->frameworkVersion)->toBe('5.1.3');
        });

        it('does not change version for Slim framework without lock match and no prefix', function () {
            $stacks = [new DetectedStack('PHP', 'Slim', '8.1', '4.0')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['some/other' => '1.0.0']);

            expect($enriched[0]->frameworkVersion)->toBe('4.0');
        });

        it('handles empty stacks array', function () {
            $detector = new PhpDetector();
            $enriched = $detector->enrichStackVersions([], ['symfony/framework-bundle' => '7.0.0']);
            expect($enriched)->toBe([]);
        });
    });

    describe('enrichDependencyVersions', function () {
        it('enriches Composer dependency version from lock', function () {
            $deps = [new DetectedDependency('doctrine/orm', '3.0', PackageManager::Composer, DependencyType::Runtime)];
            $detector = new PhpDetector();

            $enriched = $detector->enrichDependencyVersions($deps, ['doctrine/orm' => '3.4.2']);

            expect($enriched[0]->currentVersion)->toBe('3.4.2');
            expect($enriched[0]->name)->toBe('doctrine/orm');
            expect($enriched[0]->packageManager)->toBe(PackageManager::Composer);
            expect($enriched[0]->type)->toBe(DependencyType::Runtime);
        });

        it('passes through non-Composer dependencies unchanged', function () {
            $deps = [new DetectedDependency('vue', '3.5.0', PackageManager::Npm, DependencyType::Runtime)];
            $detector = new PhpDetector();

            $enriched = $detector->enrichDependencyVersions($deps, ['vue' => '3.5.13']);

            expect($enriched[0])->toBe($deps[0]);
        });

        it('keeps original version when not found in lock', function () {
            $deps = [new DetectedDependency('unknown/pkg', '1.0', PackageManager::Composer, DependencyType::Runtime)];
            $detector = new PhpDetector();

            $enriched = $detector->enrichDependencyVersions($deps, ['other/pkg' => '2.0']);

            expect($enriched[0])->toBe($deps[0]);
        });

        it('preserves repositoryUrl when enriching version', function () {
            $deps = [new DetectedDependency('a/b', '1.0', PackageManager::Composer, DependencyType::Runtime, 'https://github.com/a/b')];
            $detector = new PhpDetector();

            $enriched = $detector->enrichDependencyVersions($deps, ['a/b' => '1.2.3']);

            expect($enriched[0]->currentVersion)->toBe('1.2.3');
            expect($enriched[0]->repositoryUrl)->toBe('https://github.com/a/b');
        });

        it('handles empty dependencies array', function () {
            $detector = new PhpDetector();
            $enriched = $detector->enrichDependencyVersions([], ['a/b' => '1.0']);
            expect($enriched)->toBe([]);
        });
    });

    describe('enrichDependencyUrls', function () {
        it('enriches Composer dependency URLs from lock', function () {
            $deps = [new DetectedDependency('monolog/monolog', '3.0', PackageManager::Composer, DependencyType::Runtime)];
            $composerLock = \json_encode([
                'packages' => [
                    ['name' => 'monolog/monolog', 'version' => '3.8.0', 'source' => ['url' => 'https://github.com/Seldaek/monolog.git']],
                ],
                'packages-dev' => [],
            ]);

            $detector = new PhpDetector();
            $enriched = $detector->enrichDependencyUrls($deps, ['composer.lock' => $composerLock]);

            expect($enriched[0]->repositoryUrl)->toBe('https://github.com/Seldaek/monolog');
            expect($enriched[0]->name)->toBe('monolog/monolog');
            expect($enriched[0]->currentVersion)->toBe('3.0');
        });

        it('returns dependencies unchanged when no composer.lock', function () {
            $deps = [new DetectedDependency('a/b', '1.0', PackageManager::Composer, DependencyType::Runtime)];
            $detector = new PhpDetector();

            $enriched = $detector->enrichDependencyUrls($deps, []);

            expect($enriched[0])->toBe($deps[0]);
        });

        it('returns dependencies unchanged for invalid composer.lock JSON', function () {
            $deps = [new DetectedDependency('a/b', '1.0', PackageManager::Composer, DependencyType::Runtime)];
            $detector = new PhpDetector();

            $enriched = $detector->enrichDependencyUrls($deps, ['composer.lock' => 'invalid json']);

            expect($enriched[0])->toBe($deps[0]);
        });

        it('skips non-Composer dependencies', function () {
            $deps = [new DetectedDependency('vue', '3.5.0', PackageManager::Npm, DependencyType::Runtime)];
            $composerLock = \json_encode([
                'packages' => [['name' => 'vue', 'source' => ['url' => 'https://github.com/vuejs/core.git']]],
                'packages-dev' => [],
            ]);

            $detector = new PhpDetector();
            $enriched = $detector->enrichDependencyUrls($deps, ['composer.lock' => $composerLock]);

            expect($enriched[0])->toBe($deps[0]);
        });

        it('skips dependencies that already have a repositoryUrl', function () {
            $deps = [new DetectedDependency('a/b', '1.0', PackageManager::Composer, DependencyType::Runtime, 'https://existing.com')];
            $composerLock = \json_encode([
                'packages' => [['name' => 'a/b', 'source' => ['url' => 'https://github.com/a/b.git']]],
                'packages-dev' => [],
            ]);

            $detector = new PhpDetector();
            $enriched = $detector->enrichDependencyUrls($deps, ['composer.lock' => $composerLock]);

            expect($enriched[0]->repositoryUrl)->toBe('https://existing.com');
        });

        it('skips dependencies not found in lock URLs', function () {
            $deps = [new DetectedDependency('unknown/pkg', '1.0', PackageManager::Composer, DependencyType::Runtime)];
            $composerLock = \json_encode([
                'packages' => [['name' => 'other/pkg', 'source' => ['url' => 'https://github.com/other/pkg.git']]],
                'packages-dev' => [],
            ]);

            $detector = new PhpDetector();
            $enriched = $detector->enrichDependencyUrls($deps, ['composer.lock' => $composerLock]);

            expect($enriched[0]->repositoryUrl)->toBeNull();
        });

        it('handles empty dependencies array', function () {
            $detector = new PhpDetector();
            $enriched = $detector->enrichDependencyUrls([], ['composer.lock' => '{}']);
            expect($enriched)->toBe([]);
        });
    });
});

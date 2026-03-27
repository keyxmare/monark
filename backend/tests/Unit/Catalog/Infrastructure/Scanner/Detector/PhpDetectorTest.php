<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\PhpDetector;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

describe('PhpDetector', function () {
    it('supports composer manifest files', function () {
        $detector = new PhpDetector();
        expect($detector->supportedManifests())->toBe(['composer.json', 'composer.lock']);
    });

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

    it('returns empty when no composer.json', function () {
        $detector = new PhpDetector();
        expect($detector->detect([]))->toBe([]);
    });

    it('extracts composer dependencies filtering php and ext-', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.4', 'ext-json' => '*', 'doctrine/orm' => '^3.0'],
            'require-dev' => ['pestphp/pest' => '^4.0'],
        ]);

        $detector = new PhpDetector();
        $deps = $detector->extractDependencies(['composer.json' => $composerJson]);

        expect($deps)->toHaveCount(2);
        expect($deps[0]->name)->toBe('doctrine/orm');
        expect($deps[0]->packageManager)->toBe(PackageManager::Composer);
        expect($deps[0]->type)->toBe(DependencyType::Runtime);
        expect($deps[1]->name)->toBe('pestphp/pest');
        expect($deps[1]->type)->toBe(DependencyType::Dev);
    });

    it('enriches stack versions from lock versions', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.3', 'symfony/framework-bundle' => '^7.2'],
        ]);

        $detector = new PhpDetector();
        $stacks = $detector->detect(['composer.json' => $composerJson]);

        $lockVersions = ['symfony/framework-bundle' => '7.2.5'];
        $enriched = $detector->enrichStackVersions($stacks, $lockVersions);

        expect($enriched[0]->frameworkVersion)->toBe('7.2.5');
    });

    it('enriches dependency versions from lock versions', function () {
        $composerJson = \json_encode([
            'require' => ['doctrine/orm' => '^3.0'],
        ]);

        $detector = new PhpDetector();
        $deps = $detector->extractDependencies(['composer.json' => $composerJson]);

        $lockVersions = ['doctrine/orm' => '3.4.2'];
        $enriched = $detector->enrichDependencyVersions($deps, $lockVersions);

        expect($enriched[0]->currentVersion)->toBe('3.4.2');
    });

    it('enriches dependency URLs from composer.lock', function () {
        $composerJson = \json_encode([
            'require' => ['monolog/monolog' => '^3.0'],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                ['name' => 'monolog/monolog', 'version' => '3.8.0', 'source' => ['url' => 'https://github.com/Seldaek/monolog.git']],
            ],
            'packages-dev' => [],
        ]);

        $detector = new PhpDetector();
        $deps = $detector->extractDependencies(['composer.json' => $composerJson]);
        $enriched = $detector->enrichDependencyUrls($deps, ['composer.lock' => $composerLock]);

        expect($enriched[0]->repositoryUrl)->toBe('https://github.com/Seldaek/monolog');
    });
});

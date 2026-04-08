<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\JavaScriptDetector;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

describe('JavaScriptDetector', function () {
    it('supports package.json manifest', function () {
        $detector = new JavaScriptDetector();
        expect($detector->supportedManifests())->toBe(['package.json']);
    });

    it('detects TypeScript + Vue from package.json', function () {
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->language)->toBe('TypeScript');
        expect($stacks[0]->framework)->toBe('Vue');
        expect($stacks[0]->frameworkVersion)->toBe('3.5.0');
    });

    it('detects Angular 2+ from @angular/core', function () {
        $packageJson = \json_encode([
            'dependencies' => ['@angular/core' => '^17.3.0'],
            'devDependencies' => ['typescript' => '^5.4.0'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->language)->toBe('TypeScript');
        expect($stacks[0]->framework)->toBe('Angular');
        expect($stacks[0]->frameworkVersion)->toBe('17.3.0');
    });

    it('detects AngularJS 1.x from angular package', function () {
        $packageJson = \json_encode([
            'dependencies' => ['angular' => '^1.8.3'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->language)->toBe('JavaScript');
        expect($stacks[0]->framework)->toBe('Angular');
        expect($stacks[0]->frameworkVersion)->toBe('1.8.3');
    });

    it('prefers @angular/core over angular when both present', function () {
        $packageJson = \json_encode([
            'dependencies' => [
                '@angular/core' => '^17.3.0',
                'angular' => '^1.8.3',
            ],
            'devDependencies' => ['typescript' => '^5.4.0'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->framework)->toBe('Angular');
        expect($stacks[0]->frameworkVersion)->toBe('17.3.0');
    });

    it('detects Nuxt over Vue when both present', function () {
        $packageJson = \json_encode([
            'dependencies' => ['nuxt' => '^3.15.0', 'vue' => '^3.5.0'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        expect($stacks[0]->framework)->toBe('Nuxt');
    });

    it('detects JavaScript when no typescript dep', function () {
        $packageJson = \json_encode([
            'dependencies' => ['react' => '^18.0.0'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        expect($stacks[0]->language)->toBe('JavaScript');
        expect($stacks[0]->framework)->toBe('React');
    });

    it('reads node version from engines', function () {
        $packageJson = \json_encode([
            'engines' => ['node' => '>=22.0.0'],
            'dependencies' => ['express' => '^4.0'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        expect($stacks[0]->version)->toBe('22.0.0');
    });

    it('returns empty when no package.json', function () {
        $detector = new JavaScriptDetector();
        expect($detector->detect([]))->toBe([]);
    });

    it('extracts npm dependencies with URLs', function () {
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
            'devDependencies' => ['vite' => '^6.0.0'],
        ]);

        $detector = new JavaScriptDetector();
        $deps = $detector->extractDependencies(['package.json' => $packageJson]);

        expect($deps)->toHaveCount(2);
        expect($deps[0]->name)->toBe('vue');
        expect($deps[0]->packageManager)->toBe(PackageManager::Npm);
        expect($deps[0]->type)->toBe(DependencyType::Runtime);
        expect($deps[0]->repositoryUrl)->toBe('https://www.npmjs.com/package/vue');
        expect($deps[1]->name)->toBe('vite');
        expect($deps[1]->type)->toBe(DependencyType::Dev);
    });

    it('enriches Angular framework version from lock versions', function () {
        $packageJson = \json_encode([
            'dependencies' => ['@angular/core' => '^17.0.0'],
            'devDependencies' => ['typescript' => '^5.4.0'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        $lockVersions = ['@angular/core' => '17.3.12', 'typescript' => '5.4.5'];
        $enriched = $detector->enrichStackVersions($stacks, $lockVersions);

        expect($enriched[0]->frameworkVersion)->toBe('17.3.12');
        expect($enriched[0]->version)->toBe('5.4.5');
    });

    it('enriches AngularJS version from lock versions', function () {
        $packageJson = \json_encode([
            'dependencies' => ['angular' => '^1.8.0'],
        ]);

        $detector = new JavaScriptDetector();
        $stacks = $detector->detect(['package.json' => $packageJson]);

        $lockVersions = ['angular' => '1.8.3'];
        $enriched = $detector->enrichStackVersions($stacks, $lockVersions);

        expect($enriched[0]->frameworkVersion)->toBe('1.8.3');
    });

    it('enriches dependency versions from lock versions', function () {
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
        ]);

        $detector = new JavaScriptDetector();
        $deps = $detector->extractDependencies(['package.json' => $packageJson]);

        $lockVersions = ['vue' => '3.5.13'];
        $enriched = $detector->enrichDependencyVersions($deps, $lockVersions);

        expect($enriched[0]->currentVersion)->toBe('3.5.13');
    });
});

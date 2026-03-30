<?php

declare(strict_types=1);

use App\Dependency\Domain\Service\Strategy\ComposerVersionStrategy;
use App\Dependency\Domain\Service\Strategy\NpmVersionStrategy;
use App\Dependency\Domain\Service\Strategy\PipVersionStrategy;
use App\Dependency\Domain\Service\VersionComparisonService;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;

describe('VersionStrategyInterface implementations', function () {
    describe('ComposerVersionStrategy', function () {
        it('detects outdated when major gap exists', function () {
            $strategy = new ComposerVersionStrategy();
            $current = SemanticVersion::parse('5.4.0');
            $latest = SemanticVersion::parse('7.2.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('is not outdated when on latest', function () {
            $strategy = new ComposerVersionStrategy();
            $current = SemanticVersion::parse('7.2.0');
            $latest = SemanticVersion::parse('7.2.0');

            expect($strategy->isOutdated($current, $latest))->toBeFalse();
        });

        it('tolerates patch-level differences', function () {
            $strategy = new ComposerVersionStrategy();
            $current = SemanticVersion::parse('7.2.0');
            $latest = SemanticVersion::parse('7.2.5');

            expect($strategy->isOutdated($current, $latest))->toBeFalse();
        });

        it('detects outdated on minor gap above threshold', function () {
            $strategy = new ComposerVersionStrategy();
            $current = SemanticVersion::parse('7.0.0');
            $latest = SemanticVersion::parse('7.4.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('supports composer package manager', function () {
            expect((new ComposerVersionStrategy())->supports(PackageManager::Composer))->toBeTrue()
                ->and((new ComposerVersionStrategy())->supports(PackageManager::Npm))->toBeFalse();
        });
    });

    describe('NpmVersionStrategy', function () {
        it('detects outdated on any major gap', function () {
            $strategy = new NpmVersionStrategy();
            $current = SemanticVersion::parse('17.0.0');
            $latest = SemanticVersion::parse('18.0.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('detects outdated on minor gap above threshold', function () {
            $strategy = new NpmVersionStrategy();
            $current = SemanticVersion::parse('18.0.0');
            $latest = SemanticVersion::parse('18.3.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('tolerates small minor differences', function () {
            $strategy = new NpmVersionStrategy();
            $current = SemanticVersion::parse('18.2.0');
            $latest = SemanticVersion::parse('18.3.0');

            expect($strategy->isOutdated($current, $latest))->toBeFalse();
        });

        it('supports npm package manager', function () {
            expect((new NpmVersionStrategy())->supports(PackageManager::Npm))->toBeTrue()
                ->and((new NpmVersionStrategy())->supports(PackageManager::Pip))->toBeFalse();
        });
    });

    describe('PipVersionStrategy', function () {
        it('detects outdated on major gap', function () {
            $strategy = new PipVersionStrategy();
            $current = SemanticVersion::parse('2.0.0');
            $latest = SemanticVersion::parse('3.0.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('supports pip package manager', function () {
            expect((new PipVersionStrategy())->supports(PackageManager::Pip))->toBeTrue();
        });
    });
});

describe('VersionComparisonService', function () {
    it('delegates to correct strategy for composer', function () {
        $service = new VersionComparisonService([
            new ComposerVersionStrategy(),
            new NpmVersionStrategy(),
            new PipVersionStrategy(),
        ]);

        $current = SemanticVersion::parse('5.4.0');
        $latest = SemanticVersion::parse('7.2.0');

        expect($service->isOutdated($current, $latest, PackageManager::Composer))->toBeTrue();
    });

    it('delegates to correct strategy for npm', function () {
        $service = new VersionComparisonService([
            new ComposerVersionStrategy(),
            new NpmVersionStrategy(),
            new PipVersionStrategy(),
        ]);

        $current = SemanticVersion::parse('18.2.0');
        $latest = SemanticVersion::parse('18.3.0');

        expect($service->isOutdated($current, $latest, PackageManager::Npm))->toBeFalse();
    });

    it('throws when no strategy found', function () {
        $service = new VersionComparisonService([]);

        $service->isOutdated(
            SemanticVersion::parse('1.0.0'),
            SemanticVersion::parse('2.0.0'),
            PackageManager::Composer,
        );
    })->throws(\RuntimeException::class);
});

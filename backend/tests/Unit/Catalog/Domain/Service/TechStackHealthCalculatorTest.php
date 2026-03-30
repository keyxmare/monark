<?php

declare(strict_types=1);

use App\Catalog\Domain\Service\TechStackHealthCalculator;
use App\Catalog\Domain\ValueObject\MaintenanceStatus;
use App\Dependency\Domain\ValueObject\RiskLevel;
use App\Dependency\Domain\ValueObject\SemanticVersion;

describe('TechStackHealthCalculator', function () {
    it('returns perfect score when current equals latest and status is active', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('5.4.0'),
            latest: SemanticVersion::parse('5.4.0'),
            status: MaintenanceStatus::Active,
        );

        expect($health->getScore())->toBe(100)
            ->and($health->getRiskLevel())->toBe(RiskLevel::None);
    });

    it('deducts 50 for eol status', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('5.4.0'),
            latest: SemanticVersion::parse('5.4.0'),
            status: MaintenanceStatus::Eol,
        );

        expect($health->getScore())->toBe(50)
            ->and($health->getRiskLevel())->toBe(RiskLevel::Medium);
    });

    it('deducts 30 per major version gap', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('4.0.0'),
            latest: SemanticVersion::parse('6.0.0'),
            status: MaintenanceStatus::Active,
        );

        expect($health->getScore())->toBe(40);
    });

    it('deducts 15 for minor gap of 3 or more', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('5.1.0'),
            latest: SemanticVersion::parse('5.4.0'),
            status: MaintenanceStatus::Active,
        );

        expect($health->getScore())->toBe(85);
    });

    it('does not deduct for minor gap below 3', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('5.2.0'),
            latest: SemanticVersion::parse('5.4.0'),
            status: MaintenanceStatus::Active,
        );

        expect($health->getScore())->toBe(100);
    });

    it('clamps score at 0', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('1.0.0'),
            latest: SemanticVersion::parse('5.0.0'),
            status: MaintenanceStatus::Eol,
        );

        expect($health->getScore())->toBe(0);
    });

    it('maps risk level from score', function () {
        $calc = new TechStackHealthCalculator();

        $critical = $calc->calculate(SemanticVersion::parse('1.0.0'), SemanticVersion::parse('5.0.0'), MaintenanceStatus::Eol);
        $none = $calc->calculate(SemanticVersion::parse('5.0.0'), SemanticVersion::parse('5.0.0'), MaintenanceStatus::Active);

        expect($critical->getRiskLevel())->toBe(RiskLevel::Critical)
            ->and($none->getRiskLevel())->toBe(RiskLevel::None);
    });

    it('returns unknown health when no version info', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculateUnknown();

        expect($health->getScore())->toBe(80)
            ->and($health->isHealthy())->toBeTrue();
    });
});

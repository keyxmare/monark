<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\Severity;
use App\Dependency\Domain\ValueObject\DependencyHealth;
use App\Dependency\Domain\ValueObject\RiskLevel;

describe('DependencyHealth', function () {
    it('scores perfectly healthy dependency at 100', function () {
        $health = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [],
            isDeprecated: false,
            isNotFound: false,
        );

        expect($health->getScore())->toBe(100)
            ->and($health->isHealthy())->toBeTrue()
            ->and($health->getRiskLevel())->toBe(RiskLevel::None);
    });

    it('penalizes major version gap heavily', function () {
        $health = DependencyHealth::calculate(
            majorGap: 2,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [],
            isDeprecated: false,
            isNotFound: false,
        );

        expect($health->getScore())->toBeLessThanOrEqual(20);
    });

    it('penalizes critical vulnerabilities', function () {
        $health = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [Severity::Critical],
            isDeprecated: false,
            isNotFound: false,
        );

        expect($health->getScore())->toBeLessThanOrEqual(50)
            ->and($health->isHealthy())->toBeFalse();
    });

    it('accumulates multiple vulnerability penalties', function () {
        $single = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [Severity::High],
            isDeprecated: false,
            isNotFound: false,
        );

        $multiple = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [Severity::High, Severity::High, Severity::Medium],
            isDeprecated: false,
            isNotFound: false,
        );

        expect($multiple->getScore())->toBeLessThan($single->getScore());
    });

    it('penalizes deprecated status', function () {
        $health = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [],
            isDeprecated: true,
            isNotFound: false,
        );

        expect($health->getScore())->toBeLessThanOrEqual(70)
            ->and($health->isHealthy())->toBeFalse();
    });

    it('penalizes not found status', function () {
        $health = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [],
            isDeprecated: false,
            isNotFound: true,
        );

        expect($health->getScore())->toBeLessThanOrEqual(80);
    });

    it('never goes below 0', function () {
        $health = DependencyHealth::calculate(
            majorGap: 10,
            minorGap: 20,
            patchGap: 50,
            vulnerabilitySeverities: [Severity::Critical, Severity::Critical, Severity::Critical],
            isDeprecated: true,
            isNotFound: false,
        );

        expect($health->getScore())->toBeGreaterThanOrEqual(0);
    });

    it('maps risk level from score', function () {
        $critical = DependencyHealth::calculate(majorGap: 5, minorGap: 0, patchGap: 0, vulnerabilitySeverities: [Severity::Critical], isDeprecated: true, isNotFound: false);
        expect($critical->getRiskLevel())->toBe(RiskLevel::Critical);
    });
});

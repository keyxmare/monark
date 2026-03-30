<?php

declare(strict_types=1);

use App\Catalog\Domain\ValueObject\TechStackHealth;
use App\Dependency\Domain\ValueObject\RiskLevel;

describe('TechStackHealth', function () {
    it('has score 100 for a healthy stack', function () {
        $h = new TechStackHealth(score: 100, riskLevel: RiskLevel::None);

        expect($h->getScore())->toBe(100)
            ->and($h->getRiskLevel())->toBe(RiskLevel::None)
            ->and($h->isHealthy())->toBeTrue();
    });

    it('is not healthy when score below 60', function () {
        $h = new TechStackHealth(score: 50, riskLevel: RiskLevel::Medium);

        expect($h->isHealthy())->toBeFalse();
    });

    it('rejects score above 100', function () {
        new TechStackHealth(score: 101, riskLevel: RiskLevel::None);
    })->throws(\InvalidArgumentException::class);

    it('rejects negative score', function () {
        new TechStackHealth(score: -1, riskLevel: RiskLevel::None);
    })->throws(\InvalidArgumentException::class);

    it('is exactly healthy at 60', function () {
        $h = new TechStackHealth(score: 60, riskLevel: RiskLevel::Medium);

        expect($h->isHealthy())->toBeTrue();
    });
});

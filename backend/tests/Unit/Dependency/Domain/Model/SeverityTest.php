<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Severity;

describe('Severity', function () {
    it('has 4 cases', function () {
        expect(Severity::cases())->toHaveCount(4);
    });

    it('has correct values', function () {
        expect(Severity::Critical->value)->toBe('critical');
        expect(Severity::High->value)->toBe('high');
        expect(Severity::Medium->value)->toBe('medium');
        expect(Severity::Low->value)->toBe('low');
    });

    it('creates from valid value', function () {
        expect(Severity::from('critical'))->toBe(Severity::Critical);
        expect(Severity::from('high'))->toBe(Severity::High);
        expect(Severity::from('medium'))->toBe(Severity::Medium);
        expect(Severity::from('low'))->toBe(Severity::Low);
    });

    it('returns null for invalid value with tryFrom', function () {
        expect(Severity::tryFrom('unknown'))->toBeNull();
    });

    it('compares severity with isHigherThan', function () {
        expect(Severity::Critical->isHigherThan(Severity::High))->toBeTrue();
        expect(Severity::High->isHigherThan(Severity::Medium))->toBeTrue();
        expect(Severity::Medium->isHigherThan(Severity::Low))->toBeTrue();

        expect(Severity::Low->isHigherThan(Severity::Medium))->toBeFalse();
        expect(Severity::Medium->isHigherThan(Severity::High))->toBeFalse();
        expect(Severity::High->isHigherThan(Severity::Critical))->toBeFalse();

        expect(Severity::Critical->isHigherThan(Severity::Critical))->toBeFalse();
        expect(Severity::Low->isHigherThan(Severity::Low))->toBeFalse();
    });

    it('ranks Critical as highest severity', function () {
        expect(Severity::Critical->isHigherThan(Severity::Low))->toBeTrue();
        expect(Severity::Critical->isHigherThan(Severity::Medium))->toBeTrue();
        expect(Severity::Critical->isHigherThan(Severity::High))->toBeTrue();
    });

    it('ranks Low as lowest severity', function () {
        expect(Severity::Low->isHigherThan(Severity::Medium))->toBeFalse();
        expect(Severity::Low->isHigherThan(Severity::High))->toBeFalse();
        expect(Severity::Low->isHigherThan(Severity::Critical))->toBeFalse();
    });
});

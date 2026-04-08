<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\DependencyType;

describe('DependencyType', function () {
    it('has Runtime case with correct value', function () {
        expect(DependencyType::Runtime->value)->toBe('runtime');
    });

    it('has Dev case with correct value', function () {
        expect(DependencyType::Dev->value)->toBe('dev');
    });

    it('has exactly two cases', function () {
        expect(DependencyType::cases())->toHaveCount(2);
    });

    it('can be created from valid string', function () {
        expect(DependencyType::from('runtime'))->toBe(DependencyType::Runtime);
        expect(DependencyType::from('dev'))->toBe(DependencyType::Dev);
    });

    it('throws ValueError for invalid string', function () {
        expect(fn () => DependencyType::from('invalid'))->toThrow(ValueError::class);
    });

    it('returns null with tryFrom for invalid string', function () {
        expect(DependencyType::tryFrom('invalid'))->toBeNull();
    });
});

<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\PackageManager;

describe('PackageManager', function () {
    it('has Composer case with correct value', function () {
        expect(PackageManager::Composer->value)->toBe('composer');
    });

    it('has Npm case with correct value', function () {
        expect(PackageManager::Npm->value)->toBe('npm');
    });

    it('has Pip case with correct value', function () {
        expect(PackageManager::Pip->value)->toBe('pip');
    });

    it('has exactly three cases', function () {
        expect(PackageManager::cases())->toHaveCount(3);
    });

    it('can be created from valid string', function () {
        expect(PackageManager::from('composer'))->toBe(PackageManager::Composer);
        expect(PackageManager::from('npm'))->toBe(PackageManager::Npm);
        expect(PackageManager::from('pip'))->toBe(PackageManager::Pip);
    });

    it('throws ValueError for invalid string', function () {
        expect(fn () => PackageManager::from('yarn'))->toThrow(ValueError::class);
    });

    it('returns null with tryFrom for invalid string', function () {
        expect(PackageManager::tryFrom('yarn'))->toBeNull();
    });
});

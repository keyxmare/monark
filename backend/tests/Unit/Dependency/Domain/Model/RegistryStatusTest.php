<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\RegistryStatus;

describe('RegistryStatus', function () {
    it('has 4 cases', function () {
        expect(RegistryStatus::cases())->toHaveCount(4);
    });

    it('has correct values', function () {
        expect(RegistryStatus::Pending->value)->toBe('pending');
        expect(RegistryStatus::Synced->value)->toBe('synced');
        expect(RegistryStatus::NotFound->value)->toBe('not_found');
        expect(RegistryStatus::Deprecated->value)->toBe('deprecated');
    });

    it('creates from valid value', function () {
        expect(RegistryStatus::from('pending'))->toBe(RegistryStatus::Pending);
        expect(RegistryStatus::from('synced'))->toBe(RegistryStatus::Synced);
        expect(RegistryStatus::from('not_found'))->toBe(RegistryStatus::NotFound);
        expect(RegistryStatus::from('deprecated'))->toBe(RegistryStatus::Deprecated);
    });

    it('returns null for invalid value with tryFrom', function () {
        expect(RegistryStatus::tryFrom('invalid'))->toBeNull();
    });
});

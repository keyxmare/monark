<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProviderStatus;

describe('ProviderStatus', function () {
    it('has all expected cases', function () {
        $cases = ProviderStatus::cases();

        expect($cases)->toHaveCount(3);
        expect(ProviderStatus::Pending->value)->toBe('pending');
        expect(ProviderStatus::Connected->value)->toBe('connected');
        expect(ProviderStatus::Error->value)->toBe('error');
    });

    it('creates from valid string', function () {
        expect(ProviderStatus::from('pending'))->toBe(ProviderStatus::Pending);
        expect(ProviderStatus::from('connected'))->toBe(ProviderStatus::Connected);
        expect(ProviderStatus::from('error'))->toBe(ProviderStatus::Error);
    });

    it('returns null for invalid string with tryFrom', function () {
        expect(ProviderStatus::tryFrom('invalid'))->toBeNull();
    });
});

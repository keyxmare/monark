<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProviderType;

describe('ProviderType', function () {
    it('has all expected cases', function () {
        $cases = ProviderType::cases();

        expect($cases)->toHaveCount(3);
        expect(ProviderType::GitLab->value)->toBe('gitlab');
        expect(ProviderType::GitHub->value)->toBe('github');
        expect(ProviderType::Bitbucket->value)->toBe('bitbucket');
    });

    it('creates from valid string', function () {
        expect(ProviderType::from('gitlab'))->toBe(ProviderType::GitLab);
        expect(ProviderType::from('github'))->toBe(ProviderType::GitHub);
        expect(ProviderType::from('bitbucket'))->toBe(ProviderType::Bitbucket);
    });

    it('returns null for invalid string with tryFrom', function () {
        expect(ProviderType::tryFrom('invalid'))->toBeNull();
    });
});

<?php

declare(strict_types=1);

use App\Dependency\Application\Policy\DeprecationPolicy;
use App\Dependency\Domain\Model\RegistryStatus;

describe('DeprecationPolicy', function () {
    it('returns false when consecutive not-found count is below threshold', function () {
        $policy = new DeprecationPolicy(threshold: 3);

        expect($policy->shouldDeprecate(notFoundCount: 2))->toBeFalse();
    });

    it('returns true when consecutive not-found count reaches threshold', function () {
        $policy = new DeprecationPolicy(threshold: 3);

        expect($policy->shouldDeprecate(notFoundCount: 3))->toBeTrue();
    });

    it('returns true when consecutive not-found count exceeds threshold', function () {
        $policy = new DeprecationPolicy(threshold: 3);

        expect($policy->shouldDeprecate(notFoundCount: 5))->toBeTrue();
    });

    it('resolves correct status based on deprecation decision', function () {
        $policy = new DeprecationPolicy(threshold: 3);

        expect($policy->resolveStatus(notFoundCount: 3))->toBe(RegistryStatus::Deprecated)
            ->and($policy->resolveStatus(notFoundCount: 1))->toBe(RegistryStatus::NotFound);
    });

    it('uses default threshold of 3', function () {
        $policy = new DeprecationPolicy();

        expect($policy->shouldDeprecate(notFoundCount: 3))->toBeTrue()
            ->and($policy->shouldDeprecate(notFoundCount: 2))->toBeFalse();
    });
});

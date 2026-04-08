<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\SyncJobStatus;

describe('SyncJobStatus', function () {
    it('has all expected cases', function () {
        $cases = SyncJobStatus::cases();

        expect($cases)->toHaveCount(3);
        expect(SyncJobStatus::Running->value)->toBe('running');
        expect(SyncJobStatus::Completed->value)->toBe('completed');
        expect(SyncJobStatus::Failed->value)->toBe('failed');
    });

    it('creates from valid string', function () {
        expect(SyncJobStatus::from('running'))->toBe(SyncJobStatus::Running);
        expect(SyncJobStatus::from('completed'))->toBe(SyncJobStatus::Completed);
        expect(SyncJobStatus::from('failed'))->toBe(SyncJobStatus::Failed);
    });

    it('returns null for invalid string with tryFrom', function () {
        expect(SyncJobStatus::tryFrom('invalid'))->toBeNull();
    });
});

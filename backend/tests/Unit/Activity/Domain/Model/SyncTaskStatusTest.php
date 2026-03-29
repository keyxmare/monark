<?php

declare(strict_types=1);

use App\Activity\Domain\Model\SyncTaskStatus;

describe('SyncTaskStatus', function () {
    it('has exactly 4 cases', function () {
        expect(SyncTaskStatus::cases())->toHaveCount(4);
    });

    it('has correct values', function () {
        expect(SyncTaskStatus::Open->value)->toBe('open');
        expect(SyncTaskStatus::Acknowledged->value)->toBe('acknowledged');
        expect(SyncTaskStatus::Resolved->value)->toBe('resolved');
        expect(SyncTaskStatus::Dismissed->value)->toBe('dismissed');
    });

    it('creates from valid strings', function () {
        expect(SyncTaskStatus::from('open'))->toBe(SyncTaskStatus::Open);
        expect(SyncTaskStatus::from('acknowledged'))->toBe(SyncTaskStatus::Acknowledged);
        expect(SyncTaskStatus::from('resolved'))->toBe(SyncTaskStatus::Resolved);
        expect(SyncTaskStatus::from('dismissed'))->toBe(SyncTaskStatus::Dismissed);
    });

    it('returns null for invalid string via tryFrom', function () {
        expect(SyncTaskStatus::tryFrom('closed'))->toBeNull();
        expect(SyncTaskStatus::tryFrom(''))->toBeNull();
    });

    it('throws on invalid string via from', function () {
        expect(fn () => SyncTaskStatus::from('pending'))->toThrow(\ValueError::class);
    });
});

<?php

declare(strict_types=1);

use App\Activity\Domain\Model\SyncTaskType;

describe('SyncTaskType', function () {
    it('has exactly 5 cases', function () {
        expect(SyncTaskType::cases())->toHaveCount(5);
    });

    it('has correct values', function () {
        expect(SyncTaskType::OutdatedDependency->value)->toBe('outdated_dependency');
        expect(SyncTaskType::Vulnerability->value)->toBe('vulnerability');
        expect(SyncTaskType::StackUpgrade->value)->toBe('stack_upgrade');
        expect(SyncTaskType::NewDependency->value)->toBe('new_dependency');
        expect(SyncTaskType::StalePr->value)->toBe('stale_pr');
    });

    it('creates from valid strings', function () {
        expect(SyncTaskType::from('outdated_dependency'))->toBe(SyncTaskType::OutdatedDependency);
        expect(SyncTaskType::from('vulnerability'))->toBe(SyncTaskType::Vulnerability);
        expect(SyncTaskType::from('stack_upgrade'))->toBe(SyncTaskType::StackUpgrade);
        expect(SyncTaskType::from('new_dependency'))->toBe(SyncTaskType::NewDependency);
        expect(SyncTaskType::from('stale_pr'))->toBe(SyncTaskType::StalePr);
    });

    it('returns null for invalid string via tryFrom', function () {
        expect(SyncTaskType::tryFrom('invalid'))->toBeNull();
        expect(SyncTaskType::tryFrom(''))->toBeNull();
    });

    it('throws on invalid string via from', function () {
        expect(fn () => SyncTaskType::from('missing'))->toThrow(\ValueError::class);
    });
});

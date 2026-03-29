<?php

declare(strict_types=1);

use App\Activity\Domain\Model\SyncTaskSeverity;

describe('SyncTaskSeverity', function () {
    it('has exactly 5 cases', function () {
        expect(SyncTaskSeverity::cases())->toHaveCount(5);
    });

    it('has correct values', function () {
        expect(SyncTaskSeverity::Critical->value)->toBe('critical');
        expect(SyncTaskSeverity::High->value)->toBe('high');
        expect(SyncTaskSeverity::Medium->value)->toBe('medium');
        expect(SyncTaskSeverity::Low->value)->toBe('low');
        expect(SyncTaskSeverity::Info->value)->toBe('info');
    });

    it('creates from valid strings', function () {
        expect(SyncTaskSeverity::from('critical'))->toBe(SyncTaskSeverity::Critical);
        expect(SyncTaskSeverity::from('high'))->toBe(SyncTaskSeverity::High);
        expect(SyncTaskSeverity::from('medium'))->toBe(SyncTaskSeverity::Medium);
        expect(SyncTaskSeverity::from('low'))->toBe(SyncTaskSeverity::Low);
        expect(SyncTaskSeverity::from('info'))->toBe(SyncTaskSeverity::Info);
    });

    it('returns null for invalid string via tryFrom', function () {
        expect(SyncTaskSeverity::tryFrom('unknown'))->toBeNull();
        expect(SyncTaskSeverity::tryFrom(''))->toBeNull();
    });

    it('throws on invalid string via from', function () {
        expect(fn () => SyncTaskSeverity::from('extreme'))->toThrow(\ValueError::class);
    });
});

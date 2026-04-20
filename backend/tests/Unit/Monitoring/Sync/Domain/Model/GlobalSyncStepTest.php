<?php

declare(strict_types=1);

use App\Monitoring\Sync\Domain\Model\GlobalSyncStep;

describe('GlobalSyncStep', function (): void {
    it('has five cases', function (): void {
        expect(GlobalSyncStep::cases())->toHaveCount(5);
    });

    it('orders steps correctly', function (): void {
        expect(GlobalSyncStep::SyncProjects->value)->toBe(1)
            ->and(GlobalSyncStep::SyncCoverage->value)->toBe(2)
            ->and(GlobalSyncStep::SyncDependencies->value)->toBe(3)
            ->and(GlobalSyncStep::SyncFrameworks->value)->toBe(4)
            ->and(GlobalSyncStep::ScanCve->value)->toBe(5);
    });

    it('returns correct step names', function (): void {
        expect(GlobalSyncStep::SyncProjects->name())->toBe('sync_projects')
            ->and(GlobalSyncStep::SyncCoverage->name())->toBe('sync_coverage')
            ->and(GlobalSyncStep::SyncDependencies->name())->toBe('sync_dependencies')
            ->and(GlobalSyncStep::SyncFrameworks->name())->toBe('sync_frameworks')
            ->and(GlobalSyncStep::ScanCve->name())->toBe('scan_cve');
    });

    it('chains next steps correctly', function (): void {
        expect(GlobalSyncStep::SyncProjects->next())->toBe(GlobalSyncStep::SyncCoverage)
            ->and(GlobalSyncStep::SyncCoverage->next())->toBe(GlobalSyncStep::SyncDependencies)
            ->and(GlobalSyncStep::SyncDependencies->next())->toBe(GlobalSyncStep::SyncFrameworks)
            ->and(GlobalSyncStep::SyncFrameworks->next())->toBe(GlobalSyncStep::ScanCve)
            ->and(GlobalSyncStep::ScanCve->next())->toBeNull();
    });
});

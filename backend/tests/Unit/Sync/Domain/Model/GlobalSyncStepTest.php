<?php

declare(strict_types=1);

use App\Sync\Domain\Model\GlobalSyncStep;

describe('GlobalSyncStep', function (): void {
    it('has four cases', function (): void {
        expect(GlobalSyncStep::cases())->toHaveCount(4);
    });

    it('orders steps correctly', function (): void {
        expect(GlobalSyncStep::SyncProjects->value)->toBe(1)
            ->and(GlobalSyncStep::SyncCoverage->value)->toBe(2)
            ->and(GlobalSyncStep::SyncVersions->value)->toBe(3)
            ->and(GlobalSyncStep::ScanCve->value)->toBe(4);
    });

    it('returns correct step names', function (): void {
        expect(GlobalSyncStep::SyncProjects->name())->toBe('sync_projects')
            ->and(GlobalSyncStep::SyncCoverage->name())->toBe('sync_coverage')
            ->and(GlobalSyncStep::SyncVersions->name())->toBe('sync_versions')
            ->and(GlobalSyncStep::ScanCve->name())->toBe('scan_cve');
    });

    it('chains next steps correctly', function (): void {
        expect(GlobalSyncStep::SyncProjects->next())->toBe(GlobalSyncStep::SyncCoverage)
            ->and(GlobalSyncStep::SyncCoverage->next())->toBe(GlobalSyncStep::SyncVersions)
            ->and(GlobalSyncStep::SyncVersions->next())->toBe(GlobalSyncStep::ScanCve)
            ->and(GlobalSyncStep::ScanCve->next())->toBeNull();
    });
});

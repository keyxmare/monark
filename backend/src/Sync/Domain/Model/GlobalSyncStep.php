<?php

declare(strict_types=1);

namespace App\Sync\Domain\Model;

enum GlobalSyncStep: int
{
    case SyncProjects = 1;
    case SyncCoverage = 2;
    case SyncVersions = 3;
    case ScanCve = 4;

    public function name(): string
    {
        return match ($this) {
            self::SyncProjects => 'sync_projects',
            self::SyncCoverage => 'sync_coverage',
            self::SyncVersions => 'sync_versions',
            self::ScanCve => 'scan_cve',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::SyncProjects => self::SyncCoverage,
            self::SyncCoverage => self::SyncVersions,
            self::SyncVersions => self::ScanCve,
            self::ScanCve => null,
        };
    }
}

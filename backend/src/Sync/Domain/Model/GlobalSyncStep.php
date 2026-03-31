<?php

declare(strict_types=1);

namespace App\Sync\Domain\Model;

enum GlobalSyncStep: int
{
    case SyncProjects = 1;
    case SyncVersions = 2;
    case ScanCve = 3;

    public function name(): string
    {
        return match ($this) {
            self::SyncProjects => 'sync_projects',
            self::SyncVersions => 'sync_versions',
            self::ScanCve => 'scan_cve',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::SyncProjects => self::SyncVersions,
            self::SyncVersions => self::ScanCve,
            self::ScanCve => null,
        };
    }
}

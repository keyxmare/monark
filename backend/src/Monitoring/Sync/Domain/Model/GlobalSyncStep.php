<?php

declare(strict_types=1);

namespace App\Monitoring\Sync\Domain\Model;

enum GlobalSyncStep: int
{
    case SyncProjects = 1;
    case SyncCoverage = 2;
    case SyncDependencies = 3;
    case SyncFrameworks = 4;
    case ScanCve = 5;

    public function name(): string
    {
        return match ($this) {
            self::SyncProjects => 'sync_projects',
            self::SyncCoverage => 'sync_coverage',
            self::SyncDependencies => 'sync_dependencies',
            self::SyncFrameworks => 'sync_frameworks',
            self::ScanCve => 'scan_cve',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::SyncProjects => self::SyncCoverage,
            self::SyncCoverage => self::SyncDependencies,
            self::SyncDependencies => self::SyncFrameworks,
            self::SyncFrameworks => self::ScanCve,
            self::ScanCve => null,
        };
    }
}

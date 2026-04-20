<?php

declare(strict_types=1);

namespace App\Monitoring\Coverage\Infrastructure;

use App\Hub\Shared\Domain\Port\CoverageSummaryInterface;
use App\Monitoring\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;

final readonly class DoctrineCoverageSummary implements CoverageSummaryInterface
{
    public function __construct(
        private CoverageSnapshotRepositoryInterface $snapshots,
    ) {
    }

    public function averagePercent(): ?float
    {
        $latest = $this->snapshots->findLatestPerProject();
        if ($latest === []) {
            return null;
        }

        $sum = 0.0;
        foreach ($latest as $snapshot) {
            $sum += $snapshot->getCoveragePercent();
        }

        return $sum / \count($latest);
    }

    public function averagePercentOverAllProjects(int $totalProjects): ?float
    {
        if ($totalProjects <= 0) {
            return null;
        }

        $latest = $this->snapshots->findLatestPerProject();
        $sum = 0.0;
        foreach ($latest as $snapshot) {
            $sum += $snapshot->getCoveragePercent();
        }

        return $sum / $totalProjects;
    }
}

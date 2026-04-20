<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface CoverageSummaryInterface
{
    /**
     * Average coverage across projects that have a snapshot, as a 0..100 percent.
     * Returns null when no coverage snapshot has been recorded yet.
     */
    public function averagePercent(): ?float;

    /**
     * Average coverage across ALL tracked projects, counting projects without snapshot as 0%.
     * Reflects real health across the whole portfolio — untested projects drag the number down.
     * Returns null only when {$totalProjects} = 0.
     */
    public function averagePercentOverAllProjects(int $totalProjects): ?float;
}

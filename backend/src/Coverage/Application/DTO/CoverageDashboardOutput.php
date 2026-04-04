<?php

declare(strict_types=1);

namespace App\Coverage\Application\DTO;

final readonly class CoverageDashboardOutput
{
    /** @param list<CoverageProjectOutput> $projects */
    public function __construct(
        public CoverageSummaryOutput $summary,
        public array $projects,
    ) {
    }
}

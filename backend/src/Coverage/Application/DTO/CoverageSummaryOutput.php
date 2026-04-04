<?php

declare(strict_types=1);

namespace App\Coverage\Application\DTO;

final readonly class CoverageSummaryOutput
{
    public function __construct(
        public ?float $averageCoverage,
        public int $totalProjects,
        public int $coveredProjects,
        public int $aboveThreshold,
        public int $belowThreshold,
        public ?float $trend,
    ) {
    }
}

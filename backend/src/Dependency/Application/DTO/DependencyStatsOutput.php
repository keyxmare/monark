<?php

declare(strict_types=1);

namespace App\Dependency\Application\DTO;

final readonly class DependencyStatsOutput
{
    public function __construct(
        public int $total,
        public int $upToDate,
        public int $outdated,
        public int $totalVulnerabilities,
    ) {
    }
}

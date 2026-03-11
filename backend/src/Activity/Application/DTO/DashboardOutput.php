<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class DashboardOutput
{
    /** @param list<DashboardMetric> $metrics */
    public function __construct(
        public array $metrics,
    ) {
    }
}

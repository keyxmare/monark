<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class DashboardMetric
{
    public function __construct(
        public string $label,
        public int|string $value,
        public ?float $change = null,
    ) {
    }
}

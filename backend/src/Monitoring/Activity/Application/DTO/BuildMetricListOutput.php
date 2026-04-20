<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Application\DTO;

use App\Hub\Shared\Application\DTO\PaginatedOutput;

final readonly class BuildMetricListOutput
{
    public function __construct(
        public PaginatedOutput $data,
    ) {
    }
}

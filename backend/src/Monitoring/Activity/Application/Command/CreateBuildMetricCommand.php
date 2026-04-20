<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Application\Command;

use App\Monitoring\Activity\Application\DTO\CreateBuildMetricInput;

final readonly class CreateBuildMetricCommand
{
    public function __construct(
        public string $projectId,
        public CreateBuildMetricInput $input,
    ) {
    }
}

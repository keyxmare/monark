<?php

declare(strict_types=1);

namespace App\Activity\Application\Command;

use App\Activity\Application\DTO\CreateBuildMetricInput;

final readonly class CreateBuildMetricCommand
{
    public function __construct(
        public string $projectId,
        public CreateBuildMetricInput $input,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Command;

use App\Monitoring\Catalog\Application\DTO\UpdateProjectInput;

final readonly class UpdateProjectCommand
{
    public function __construct(
        public string $projectId,
        public UpdateProjectInput $input,
    ) {
    }
}

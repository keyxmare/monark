<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\UpdateProjectInput;

final readonly class UpdateProjectCommand
{
    public function __construct(
        public string $projectId,
        public UpdateProjectInput $input,
    ) {
    }
}

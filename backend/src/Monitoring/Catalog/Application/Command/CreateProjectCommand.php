<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Command;

use App\Monitoring\Catalog\Application\DTO\CreateProjectInput;

final readonly class CreateProjectCommand
{
    public function __construct(
        public CreateProjectInput $input,
    ) {
    }
}

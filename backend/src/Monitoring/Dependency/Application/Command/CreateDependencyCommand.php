<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\Command;

use App\Monitoring\Dependency\Application\DTO\CreateDependencyInput;

final readonly class CreateDependencyCommand
{
    public function __construct(
        public CreateDependencyInput $input,
    ) {
    }
}

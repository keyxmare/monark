<?php

declare(strict_types=1);

namespace App\Dependency\Application\Command;

use App\Dependency\Application\DTO\CreateDependencyInput;

final readonly class CreateDependencyCommand
{
    public function __construct(
        public CreateDependencyInput $input,
    ) {
    }
}

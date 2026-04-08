<?php

declare(strict_types=1);

namespace App\Dependency\Application\Command;

use App\Dependency\Application\DTO\UpdateDependencyInput;

final readonly class UpdateDependencyCommand
{
    public function __construct(
        public string $dependencyId,
        public UpdateDependencyInput $input,
    ) {
    }
}

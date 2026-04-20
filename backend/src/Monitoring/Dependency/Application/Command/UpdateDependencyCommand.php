<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\Command;

use App\Monitoring\Dependency\Application\DTO\UpdateDependencyInput;

final readonly class UpdateDependencyCommand
{
    public function __construct(
        public string $dependencyId,
        public UpdateDependencyInput $input,
    ) {
    }
}

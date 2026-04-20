<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\Command;

final readonly class DeleteDependencyCommand
{
    public function __construct(
        public string $dependencyId,
    ) {
    }
}

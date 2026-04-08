<?php

declare(strict_types=1);

namespace App\Dependency\Application\Command;

final readonly class SyncDependencyCveCommand
{
    public function __construct(
        public string $projectId,
    ) {
    }
}

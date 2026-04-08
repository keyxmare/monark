<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class DependencyCreated
{
    public function __construct(
        public string $dependencyId,
        public string $name,
        public string $packageManager,
        public string $currentVersion,
        public string $projectId,
    ) {
    }
}

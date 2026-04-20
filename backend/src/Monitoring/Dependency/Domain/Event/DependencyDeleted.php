<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Event;

final readonly class DependencyDeleted
{
    public function __construct(
        public string $dependencyId,
        public string $name,
        public string $packageManager,
    ) {
    }
}

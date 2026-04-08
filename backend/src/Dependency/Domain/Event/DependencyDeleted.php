<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class DependencyDeleted
{
    public function __construct(
        public string $dependencyId,
        public string $name,
        public string $packageManager,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class DependencyUpdated
{
    public function __construct(
        public string $dependencyId,
        public string $name,
    ) {
    }
}

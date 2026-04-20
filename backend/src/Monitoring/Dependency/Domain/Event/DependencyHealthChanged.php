<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Event;

final readonly class DependencyHealthChanged
{
    public function __construct(
        public string $dependencyId,
        public string $name,
        public int $previousScore,
        public int $newScore,
        public string $riskLevel,
    ) {
    }
}

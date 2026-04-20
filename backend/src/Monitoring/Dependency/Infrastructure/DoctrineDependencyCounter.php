<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Infrastructure;

use App\Hub\Shared\Domain\Port\DependencyCounterInterface;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;

final readonly class DoctrineDependencyCounter implements DependencyCounterInterface
{
    public function __construct(
        private DependencyRepositoryInterface $dependencies,
    ) {
    }

    public function countTracked(): int
    {
        return $this->dependencies->count();
    }
}

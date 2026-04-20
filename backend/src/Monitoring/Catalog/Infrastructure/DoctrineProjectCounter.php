<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Infrastructure;

use App\Hub\Shared\Domain\Port\ProjectCounterInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;

final readonly class DoctrineProjectCounter implements ProjectCounterInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
    ) {
    }

    public function countTracked(): int
    {
        return $this->projects->count();
    }
}

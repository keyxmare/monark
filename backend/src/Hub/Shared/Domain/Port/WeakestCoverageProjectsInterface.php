<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface WeakestCoverageProjectsInterface
{
    /**
     * Top-N projects ordered by latest coverage percent, ascending.
     *
     * @return list<array{id: string, name: string, slug: string, coveragePercent: float}>
     */
    public function lowest(int $limit): array;
}

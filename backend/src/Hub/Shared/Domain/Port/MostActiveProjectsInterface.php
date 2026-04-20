<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface MostActiveProjectsInterface
{
    /**
     * Top-N projects ordered by commit count over the last {$windowDays} days, descending.
     *
     * @return list<array{id: string, name: string, slug: string, commits: int}>
     */
    public function top(int $limit, int $windowDays): array;
}

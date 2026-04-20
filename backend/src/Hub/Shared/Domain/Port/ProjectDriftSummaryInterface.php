<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface ProjectDriftSummaryInterface
{
    /**
     * Framework drift breakdown per project, ordered by most drift first (drift desc, then upToDate asc).
     *
     * @return list<array{name: string, slug: string, total: int, upToDate: int, drift: int}>
     */
    public function byProject(int $limit): array;
}

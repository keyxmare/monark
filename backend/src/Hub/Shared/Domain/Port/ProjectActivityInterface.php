<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

use App\Hub\Shared\Application\DTO\ProjectActivitySummary;
use Symfony\Component\Uid\Uuid;

interface ProjectActivityInterface
{
    /**
     * Activity summary per project over a rolling window of days.
     *
     * @param list<Uuid> $projectIds
     * @param int        $windowDays Number of days to aggregate (e.g. 30)
     *
     * @return array<string, ProjectActivitySummary> Keyed by project UUID (RFC 4122).
     *                                               Missing projects are NOT in the map — callers should fall back to empty().
     */
    public function summarizeForProjects(array $projectIds, int $windowDays): array;
}

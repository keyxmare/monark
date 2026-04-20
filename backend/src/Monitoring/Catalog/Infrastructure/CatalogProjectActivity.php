<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Infrastructure;

use App\Hub\Shared\Application\DTO\ProjectActivitySummary;
use App\Hub\Shared\Domain\Port\ProjectActivityInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;

final readonly class CatalogProjectActivity implements ProjectActivityInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
    ) {
    }

    public function summarizeForProjects(array $projectIds, int $windowDays): array
    {
        $out = [];
        foreach ($projectIds as $projectId) {
            $project = $this->projects->findById($projectId);
            if ($project === null) {
                continue;
            }

            $series = $project->getCommitsDailySeries();
            if (\count($series) !== $windowDays) {
                $series = $this->resize($series, $windowDays);
            }

            $out[$projectId->toRfc4122()] = new ProjectActivitySummary(
                commitsCount: (int) \array_sum($series),
                lastActivityAt: $project->getLastCommitAt(),
                lastCommitSha: $project->getLastCommitSha(),
                dailyCommitCounts: $series,
            );
        }

        return $out;
    }

    /**
     * @param list<int> $series
     *
     * @return list<int>
     */
    private function resize(array $series, int $windowDays): array
    {
        $length = \count($series);
        if ($length === 0) {
            return \array_fill(0, $windowDays, 0);
        }
        if ($length > $windowDays) {
            return \array_slice($series, -$windowDays);
        }

        return \array_merge(\array_fill(0, $windowDays - $length, 0), $series);
    }
}

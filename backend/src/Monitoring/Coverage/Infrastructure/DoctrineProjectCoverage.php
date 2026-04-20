<?php

declare(strict_types=1);

namespace App\Monitoring\Coverage\Infrastructure;

use App\Hub\Shared\Domain\Port\ProjectCoverageInterface;
use App\Monitoring\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;

final readonly class DoctrineProjectCoverage implements ProjectCoverageInterface
{
    public function __construct(
        private CoverageSnapshotRepositoryInterface $snapshots,
    ) {
    }

    public function findLatestForProjects(array $projectIds): array
    {
        return $this->snapshots->findLatestPercentForProjects($projectIds);
    }

    public function findLatestJobsForProjects(array $projectIds): array
    {
        return $this->snapshots->findLatestJobsForProjects($projectIds);
    }
}

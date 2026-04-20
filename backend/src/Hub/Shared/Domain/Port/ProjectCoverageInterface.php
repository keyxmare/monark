<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

interface ProjectCoverageInterface
{
    /**
     * Latest coverage percent (0..100) per project.
     *
     * @param list<Uuid> $projectIds
     *
     * @return array<string, float> Keyed by project UUID (RFC 4122). Projects with no snapshot are absent.
     */
    public function findLatestForProjects(array $projectIds): array;

    /**
     * Latest CI jobs coverage breakdown per project.
     *
     * @param list<Uuid> $projectIds
     *
     * @return array<string, list<array{name: string, percent: float}>> Keyed by project UUID (RFC 4122). Projects with no jobs are absent.
     */
    public function findLatestJobsForProjects(array $projectIds): array;
}

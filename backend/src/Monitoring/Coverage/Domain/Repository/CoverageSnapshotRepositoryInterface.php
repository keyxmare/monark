<?php

declare(strict_types=1);

namespace App\Monitoring\Coverage\Domain\Repository;

use App\Monitoring\Coverage\Domain\Model\CoverageSnapshot;
use Symfony\Component\Uid\Uuid;

interface CoverageSnapshotRepositoryInterface
{
    public function save(CoverageSnapshot $snapshot): void;

    public function findLatestByProject(Uuid $projectId): ?CoverageSnapshot;

    /** @return list<CoverageSnapshot> */
    public function findAllByProject(Uuid $projectId, int $limit = 50): array;

    /** @return list<CoverageSnapshot> Returns one snapshot per project (the latest) */
    public function findLatestPerProject(): array;

    /** @return list<CoverageSnapshot> Returns the second-latest per project (for trend) */
    public function findPreviousPerProject(): array;

    /**
     * @param list<Uuid> $projectIds
     *
     * @return array<string, float> Latest coverage percent keyed by project UUID (RFC 4122).
     *                              Projects without a snapshot are absent from the map.
     */
    public function findLatestPercentForProjects(array $projectIds): array;

    /**
     * @param list<Uuid> $projectIds
     *
     * @return array<string, list<array{name: string, percent: float}>> Latest CI jobs breakdown keyed by project UUID (RFC 4122).
     *                                                                  Projects without jobs are absent from the map.
     */
    public function findLatestJobsForProjects(array $projectIds): array;
}

<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Domain\Repository;

use App\Monitoring\Activity\Domain\Model\BuildMetric;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

interface BuildMetricRepositoryInterface
{
    public function findById(Uuid $id): ?BuildMetric;

    /** @return list<BuildMetric> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array;

    public function countByProjectId(Uuid $projectId): int;

    public function findLatestByProjectId(Uuid $projectId): ?BuildMetric;

    public function save(BuildMetric $buildMetric): void;

    /**
     * Daily distinct commitSha counts per project over a rolling window.
     *
     * @param list<Uuid> $projectIds
     * @param int        $windowDays Number of days (ending today, UTC)
     *
     * @return array<string, array{total: int, lastAt: DateTimeImmutable, lastSha: string, daily: list<int>}>
     *                                Keyed by project UUID (RFC 4122). `daily` is ordered oldest→newest, length = windowDays.
     *                                Projects with no build metric are absent.
     */
    public function summarizeForProjects(array $projectIds, int $windowDays): array;
}

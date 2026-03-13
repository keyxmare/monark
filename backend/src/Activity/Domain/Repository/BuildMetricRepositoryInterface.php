<?php

declare(strict_types=1);

namespace App\Activity\Domain\Repository;

use App\Activity\Domain\Model\BuildMetric;
use Symfony\Component\Uid\Uuid;

interface BuildMetricRepositoryInterface
{
    public function findById(Uuid $id): ?BuildMetric;

    /** @return list<BuildMetric> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array;

    public function countByProjectId(Uuid $projectId): int;

    public function findLatestByProjectId(Uuid $projectId): ?BuildMetric;

    public function save(BuildMetric $buildMetric): void;
}

<?php

declare(strict_types=1);

namespace App\Coverage\Domain\Repository;

use App\Coverage\Domain\Model\CoverageSnapshot;
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
}

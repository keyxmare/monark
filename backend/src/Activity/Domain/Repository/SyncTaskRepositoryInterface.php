<?php

declare(strict_types=1);

namespace App\Activity\Domain\Repository;

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use Symfony\Component\Uid\Uuid;

interface SyncTaskRepositoryInterface
{
    public function findById(Uuid $id): ?SyncTask;

    /** @return list<SyncTask> */
    public function findFiltered(
        ?SyncTaskStatus $status = null,
        ?SyncTaskType $type = null,
        ?SyncTaskSeverity $severity = null,
        ?Uuid $projectId = null,
        int $page = 1,
        int $perPage = 20,
    ): array;

    public function countFiltered(
        ?SyncTaskStatus $status = null,
        ?SyncTaskType $type = null,
        ?SyncTaskSeverity $severity = null,
        ?Uuid $projectId = null,
    ): int;

    public function findOpenByProjectAndTypeAndKey(Uuid $projectId, SyncTaskType $type, string $metadataKey): ?SyncTask;

    /** @return list<array{label: string, count: int}> */
    public function countGroupedByType(): array;

    /** @return list<array{label: string, count: int}> */
    public function countGroupedBySeverity(): array;

    /** @return list<array{label: string, count: int}> */
    public function countGroupedByStatus(): array;

    public function save(SyncTask $syncTask): void;
}

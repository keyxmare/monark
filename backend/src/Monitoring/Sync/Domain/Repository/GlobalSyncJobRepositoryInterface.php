<?php

declare(strict_types=1);

namespace App\Monitoring\Sync\Domain\Repository;

use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use Symfony\Component\Uid\Uuid;

interface GlobalSyncJobRepositoryInterface
{
    public function save(GlobalSyncJob $job): void;

    public function findById(Uuid $id): ?GlobalSyncJob;

    public function findRunning(): ?GlobalSyncJob;

    /** @return array{progress: int, total: int} */
    public function incrementProgressAtomic(Uuid $jobId): array;

    /** @return array{progress: int, total: int} */
    public function incrementProgressByAtomic(Uuid $jobId, int $delta): array;

    public function findByIdForUpdate(Uuid $id): ?GlobalSyncJob;
}

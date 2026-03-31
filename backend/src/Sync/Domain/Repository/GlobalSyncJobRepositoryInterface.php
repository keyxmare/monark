<?php

declare(strict_types=1);

namespace App\Sync\Domain\Repository;

use App\Sync\Domain\Model\GlobalSyncJob;
use Symfony\Component\Uid\Uuid;

interface GlobalSyncJobRepositoryInterface
{
    public function save(GlobalSyncJob $job): void;

    public function findById(Uuid $id): ?GlobalSyncJob;

    public function findRunning(): ?GlobalSyncJob;
}

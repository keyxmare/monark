<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Repository;

use App\Monitoring\Catalog\Domain\Model\SyncJob;
use Symfony\Component\Uid\Uuid;

interface SyncJobRepositoryInterface
{
    public function findById(Uuid $id): ?SyncJob;

    public function save(SyncJob $syncJob): void;
}

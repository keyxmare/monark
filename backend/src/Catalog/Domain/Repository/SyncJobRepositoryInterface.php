<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\SyncJob;
use Symfony\Component\Uid\Uuid;

interface SyncJobRepositoryInterface
{
    public function findById(Uuid $id): ?SyncJob;

    public function save(SyncJob $syncJob): void;
}

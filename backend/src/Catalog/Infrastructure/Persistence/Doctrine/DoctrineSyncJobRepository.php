<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineSyncJobRepository implements SyncJobRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?SyncJob
    {
        return $this->entityManager->getRepository(SyncJob::class)->find($id);
    }

    public function save(SyncJob $syncJob): void
    {
        $this->entityManager->persist($syncJob);
        $this->entityManager->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\Sync\Infrastructure\Repository;

use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStatus;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineGlobalSyncJobRepository implements GlobalSyncJobRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function save(GlobalSyncJob $job): void
    {
        $this->em->persist($job);
        $this->em->flush();
    }

    public function findById(Uuid $id): ?GlobalSyncJob
    {
        return $this->em->find(GlobalSyncJob::class, $id);
    }

    public function findRunning(): ?GlobalSyncJob
    {
        return $this->em->getRepository(GlobalSyncJob::class)->findOneBy([
            'status' => GlobalSyncStatus::Running,
        ]);
    }
}

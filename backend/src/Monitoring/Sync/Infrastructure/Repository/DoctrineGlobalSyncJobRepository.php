<?php

declare(strict_types=1);

namespace App\Monitoring\Sync\Infrastructure\Repository;

use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use App\Monitoring\Sync\Domain\Model\GlobalSyncStatus;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
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

    /** @return array{progress: int, total: int} */
    public function incrementProgressAtomic(Uuid $jobId): array
    {
        return $this->incrementProgressByAtomic($jobId, 1);
    }

    /** @return array{progress: int, total: int} */
    public function incrementProgressByAtomic(Uuid $jobId, int $delta): array
    {
        $conn = $this->em->getConnection();

        /** @var array{step_progress: int, step_total: int} $row */
        $row = $conn->fetchAssociative(
            'UPDATE global_sync_jobs SET step_progress = step_progress + :delta WHERE id = :id RETURNING step_progress, step_total',
            ['delta' => $delta, 'id' => $jobId->toRfc4122()],
        );

        $this->em->clear();

        return ['progress' => (int) $row['step_progress'], 'total' => (int) $row['step_total']];
    }

    public function findByIdForUpdate(Uuid $id): ?GlobalSyncJob
    {
        /** @var GlobalSyncJob|null */
        return $this->em->createQueryBuilder()
            ->select('j')
            ->from(GlobalSyncJob::class, 'j')
            ->where('j.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }
}

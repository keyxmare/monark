<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Persistence\Doctrine;

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineSyncTaskRepository implements SyncTaskRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?SyncTask
    {
        return $this->entityManager->getRepository(SyncTask::class)->find($id);
    }

    /** @return list<SyncTask> */
    public function findFiltered(
        ?SyncTaskStatus $status = null,
        ?SyncTaskType $type = null,
        ?SyncTaskSeverity $severity = null,
        ?Uuid $projectId = null,
        int $page = 1,
        int $perPage = 20,
    ): array {
        $qb = $this->entityManager->getRepository(SyncTask::class)
            ->createQueryBuilder('st')
            ->orderBy('st.createdAt', 'DESC');

        $this->applyFilters($qb, $status, $type, $severity, $projectId);

        /** @var list<SyncTask> */
        return $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countFiltered(
        ?SyncTaskStatus $status = null,
        ?SyncTaskType $type = null,
        ?SyncTaskSeverity $severity = null,
        ?Uuid $projectId = null,
    ): int {
        $qb = $this->entityManager->getRepository(SyncTask::class)
            ->createQueryBuilder('st')
            ->select('COUNT(st.id)');

        $this->applyFilters($qb, $status, $type, $severity, $projectId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findOpenByProjectAndTypeAndKey(Uuid $projectId, SyncTaskType $type, string $metadataKey): ?SyncTask
    {
        /** @var list<SyncTask> */
        $results = $this->entityManager->getRepository(SyncTask::class)
            ->createQueryBuilder('st')
            ->where('st.projectId = :projectId')
            ->andWhere('st.type = :type')
            ->andWhere('st.status IN (:openStatuses)')
            ->setParameter('projectId', $projectId)
            ->setParameter('type', $type->value)
            ->setParameter('openStatuses', [SyncTaskStatus::Open->value, SyncTaskStatus::Acknowledged->value])
            ->getQuery()
            ->getResult();

        foreach ($results as $task) {
            if ($task->getMetadataKey() === $metadataKey) {
                return $task;
            }
        }

        return null;
    }

    /** @return list<array{label: string, count: int}> */
    public function countGroupedByType(): array
    {
        return $this->countGroupedBy('st.type');
    }

    /** @return list<array{label: string, count: int}> */
    public function countGroupedBySeverity(): array
    {
        return $this->countGroupedBy('st.severity');
    }

    /** @return list<array{label: string, count: int}> */
    public function countGroupedByStatus(): array
    {
        return $this->countGroupedBy('st.status');
    }

    public function save(SyncTask $syncTask): void
    {
        $this->entityManager->persist($syncTask);
        $this->entityManager->flush();
    }

    /** @return list<array{label: string, count: int}> */
    private function countGroupedBy(string $field): array
    {
        /** @var list<array{label: string, count: int}> */
        $results = $this->entityManager->getRepository(SyncTask::class)
            ->createQueryBuilder('st')
            ->select(\sprintf('%s AS label, COUNT(st.id) AS count', $field))
            ->groupBy($field)
            ->getQuery()
            ->getResult();

        return \array_map(
            static fn (array $row) => ['label' => $row['label'] instanceof \BackedEnum ? $row['label']->value : (string) $row['label'], 'count' => (int) $row['count']],
            $results,
        );
    }

    private function applyFilters(
        \Doctrine\ORM\QueryBuilder $qb,
        ?SyncTaskStatus $status,
        ?SyncTaskType $type,
        ?SyncTaskSeverity $severity,
        ?Uuid $projectId,
    ): void {
        if ($status !== null) {
            $qb->andWhere('st.status = :status')->setParameter('status', $status->value);
        }
        if ($type !== null) {
            $qb->andWhere('st.type = :type')->setParameter('type', $type->value);
        }
        if ($severity !== null) {
            $qb->andWhere('st.severity = :severity')->setParameter('severity', $severity->value);
        }
        if ($projectId !== null) {
            $qb->andWhere('st.projectId = :projectId')->setParameter('projectId', $projectId);
        }
    }
}

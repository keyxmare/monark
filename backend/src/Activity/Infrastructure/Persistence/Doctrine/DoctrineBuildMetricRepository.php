<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Persistence\Doctrine;

use App\Activity\Domain\Model\BuildMetric;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineBuildMetricRepository implements BuildMetricRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?BuildMetric
    {
        return $this->entityManager->getRepository(BuildMetric::class)->find($id);
    }

    /** @return list<BuildMetric> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
    {
        /** @var list<BuildMetric> */
        return $this->entityManager->getRepository(BuildMetric::class)
            ->createQueryBuilder('bm')
            ->andWhere('bm.projectId = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('bm.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countByProjectId(Uuid $projectId): int
    {
        return (int) $this->entityManager->getRepository(BuildMetric::class)
            ->createQueryBuilder('bm')
            ->select('COUNT(bm.id)')
            ->andWhere('bm.projectId = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLatestByProjectId(Uuid $projectId): ?BuildMetric
    {
        /** @var ?BuildMetric */
        return $this->entityManager->getRepository(BuildMetric::class)
            ->createQueryBuilder('bm')
            ->andWhere('bm.projectId = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('bm.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(BuildMetric $buildMetric): void
    {
        $this->entityManager->persist($buildMetric);
        $this->entityManager->flush();
    }
}

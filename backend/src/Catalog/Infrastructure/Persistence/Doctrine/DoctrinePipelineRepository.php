<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Pipeline;
use App\Catalog\Domain\Repository\PipelineRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrinePipelineRepository implements PipelineRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Pipeline
    {
        return $this->entityManager->getRepository(Pipeline::class)->find($id);
    }

    /** @return list<Pipeline> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(Pipeline::class)
            ->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Pipeline> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, ?string $ref = null): array
    {
        $qb = $this->entityManager->getRepository(Pipeline::class)
            ->createQueryBuilder('p')
            ->where('p.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($ref !== null) {
            $qb->andWhere('p.ref = :ref')
                ->setParameter('ref', $ref);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByProjectId(Uuid $projectId, ?string $ref = null): int
    {
        $qb = $this->entityManager->getRepository(Pipeline::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.project = :projectId')
            ->setParameter('projectId', $projectId);

        if ($ref !== null) {
            $qb->andWhere('p.ref = :ref')
                ->setParameter('ref', $ref);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Pipeline::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Pipeline $pipeline): void
    {
        $this->entityManager->persist($pipeline);
        $this->entityManager->flush();
    }
}

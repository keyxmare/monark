<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineTechStackRepository implements TechStackRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?TechStack
    {
        return $this->entityManager->getRepository(TechStack::class)->find($id);
    }

    /** @return list<TechStack> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        /** @var list<TechStack> */
        return $this->entityManager->getRepository(TechStack::class)
            ->createQueryBuilder('ts')
            ->orderBy('ts.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @return list<TechStack> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
    {
        /** @var list<TechStack> */
        return $this->entityManager->getRepository(TechStack::class)
            ->createQueryBuilder('ts')
            ->where('ts.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('ts.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countByProjectId(Uuid $projectId): int
    {
        return (int) $this->entityManager->getRepository(TechStack::class)
            ->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->where('ts.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(TechStack::class)
            ->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(TechStack $techStack): void
    {
        $this->entityManager->persist($techStack);
        $this->entityManager->flush();
    }

    public function delete(TechStack $techStack): void
    {
        $this->entityManager->remove($techStack);
        $this->entityManager->flush();
    }

    public function deleteByProjectId(Uuid $projectId): void
    {
        $this->entityManager->getRepository(TechStack::class)
            ->createQueryBuilder('ts')
            ->delete()
            ->where('ts.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->execute();
    }
}

<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Persistence\Doctrine;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineDependencyRepository implements DependencyRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Dependency
    {
        return $this->entityManager->getRepository(Dependency::class)->find($id);
    }

    /** @return list<Dependency> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Dependency $dependency): void
    {
        $this->entityManager->persist($dependency);
        $this->entityManager->flush();
    }

    /** @return list<Dependency> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->where('d.projectId = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('d.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function delete(Dependency $dependency): void
    {
        $this->entityManager->remove($dependency);
        $this->entityManager->flush();
    }

    public function deleteByProjectId(Uuid $projectId): void
    {
        $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->delete()
            ->where('d.projectId = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->execute();
    }
}

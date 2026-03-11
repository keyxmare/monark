<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Project
    {
        return $this->entityManager->getRepository(Project::class)->find($id);
    }

    public function findBySlug(string $slug): ?Project
    {
        return $this->entityManager->getRepository(Project::class)->findOneBy(['slug' => $slug]);
    }

    public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
    {
        return $this->entityManager->getRepository(Project::class)
            ->findOneBy(['externalId' => $externalId, 'provider' => $providerId]);
    }

    /** @return list<string> */
    public function findExternalIdsByProvider(Uuid $providerId): array
    {
        $results = $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->select('p.externalId')
            ->where('p.provider = :providerId')
            ->andWhere('p.externalId IS NOT NULL')
            ->setParameter('providerId', $providerId)
            ->getQuery()
            ->getSingleColumnResult();

        return \array_values(\array_filter($results));
    }

    /** @return list<Project> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Project> */
    public function findByProviderId(Uuid $providerId): array
    {
        return $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->where('p.provider = :providerId')
            ->setParameter('providerId', $providerId)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Project> */
    public function findAllWithProvider(): array
    {
        return $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->where('p.provider IS NOT NULL')
            ->andWhere('p.externalId IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Project $project): void
    {
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }

    public function delete(Project $project): void
    {
        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }
}

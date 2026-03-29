<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Project
    {
        /** @var Project|null */
        return $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.techStacks', 'ts')->addSelect('ts')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySlug(string $slug): ?Project
    {
        /** @var Project|null */
        return $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.techStacks', 'ts')->addSelect('ts')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
    {
        return $this->entityManager->getRepository(Project::class)
            ->findOneBy(['externalId' => $externalId, 'provider' => $providerId]);
    }

    /** @return array<string, string> */
    public function findExternalIdMapByProvider(Uuid $providerId): array
    {
        /** @var list<array{externalId: string, id: \Symfony\Component\Uid\Uuid}> $results */
        $results = $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->select('p.externalId', 'p.id')
            ->where('p.provider = :providerId')
            ->andWhere('p.externalId IS NOT NULL')
            ->setParameter('providerId', $providerId)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($results as $row) {
            $map[$row['externalId']] = (string) $row['id'];
        }

        return $map;
    }

    /** @return list<Project> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        $query = $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.techStacks', 'ts')->addSelect('ts')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        /** @var list<Project> */
        return \iterator_to_array(new Paginator($query));
    }

    /** @return list<Project> */
    public function findByProviderId(Uuid $providerId): array
    {
        /** @var list<Project> */
        return $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.techStacks', 'ts')->addSelect('ts')
            ->where('p.provider = :providerId')
            ->setParameter('providerId', $providerId)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Project> */
    public function findAllWithProvider(): array
    {
        /** @var list<Project> */
        return $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.techStacks', 'ts')->addSelect('ts')
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

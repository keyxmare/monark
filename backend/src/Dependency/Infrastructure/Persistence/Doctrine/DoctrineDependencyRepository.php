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
        /** @var list<Dependency> */
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
        /** @var list<Dependency> */
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

    public function countByProjectId(Uuid $projectId): int
    {
        return (int) $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.projectId = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getSingleScalarResult();
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

    /** @param array{projectId?: string, search?: string, packageManager?: string, type?: string, isOutdated?: bool, sort?: string, sortDir?: string} $filters */
    public function findFiltered(int $page, int $perPage, array $filters = []): array
    {
        $qb = $this->entityManager->getRepository(Dependency::class)->createQueryBuilder('d');
        $this->applyFilters($qb, $filters);

        $sort = $filters['sort'] ?? 'name';
        $sortDir = \strtoupper($filters['sortDir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $sortField = match ($sort) {
            'currentVersion' => 'd.currentVersion',
            'packageManager' => 'd.packageManager',
            'type' => 'd.type',
            'isOutdated' => 'd.isOutdated',
            default => 'd.name',
        };
        $qb->orderBy($sortField, $sortDir);

        /** @var list<Dependency> */
        return $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @param array{projectId?: string, search?: string, packageManager?: string, type?: string, isOutdated?: bool} $filters */
    public function countFiltered(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(Dependency::class)->createQueryBuilder('d');
        $qb->select('COUNT(d.id)');
        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['projectId']) && $filters['projectId'] !== '') {
            $qb->andWhere('d.projectId = :projectId')
                ->setParameter('projectId', $filters['projectId']);
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $qb->andWhere('d.name LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }
        if (isset($filters['packageManager']) && $filters['packageManager'] !== '') {
            $qb->andWhere('d.packageManager = :pm')
                ->setParameter('pm', $filters['packageManager']);
        }
        if (isset($filters['type']) && $filters['type'] !== '') {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $filters['type']);
        }
        if (isset($filters['isOutdated'])) {
            $qb->andWhere('d.isOutdated = :isOutdated')
                ->setParameter('isOutdated', $filters['isOutdated']);
        }
    }

    public function findUniquePackages(): array
    {
        /** @var list<array{name: string, packageManager: string}> */
        return $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->select('d.name, d.packageManager')
            ->groupBy('d.name, d.packageManager')
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name, string $packageManager): array
    {
        /** @var list<Dependency> */
        return $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->where('d.name = :name')
            ->andWhere('d.packageManager = :pm')
            ->setParameter('name', $name)
            ->setParameter('pm', $packageManager)
            ->getQuery()
            ->getResult();
    }

    /** @param array{projectId?: string, packageManager?: string, type?: string} $filters */
    public function getStats(array $filters = []): array
    {
        $repo = $this->entityManager->getRepository(Dependency::class);

        $qbTotal = $repo->createQueryBuilder('d')->select('COUNT(d.id)');
        $this->applyFilters($qbTotal, $filters);
        $total = (int) $qbTotal->getQuery()->getSingleScalarResult();

        $qbOutdated = $repo->createQueryBuilder('d')->select('COUNT(d.id)')->andWhere('d.isOutdated = true');
        $this->applyFilters($qbOutdated, $filters);
        $outdated = (int) $qbOutdated->getQuery()->getSingleScalarResult();

        $qbVulns = $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from('App\Dependency\Domain\Model\Vulnerability', 'v')
            ->join('App\Dependency\Domain\Model\Dependency', 'd', 'WITH', 'v.dependency = d.id');
        $this->applyFilters($qbVulns, $filters);
        $totalVulnerabilities = (int) $qbVulns->getQuery()->getSingleScalarResult();

        return [
            'total' => $total,
            'outdated' => $outdated,
            'totalVulnerabilities' => $totalVulnerabilities,
        ];
    }
}

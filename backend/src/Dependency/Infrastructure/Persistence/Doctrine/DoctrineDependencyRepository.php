<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Persistence\Doctrine;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineDependencyRepository implements DependencyRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Dependency
    {
        /** @var Dependency|null */
        return $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->leftJoin('d.vulnerabilities', 'v')->addSelect('v')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<Dependency> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        $query = $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->leftJoin('d.vulnerabilities', 'v')->addSelect('v')
            ->orderBy('d.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        /** @var list<Dependency> */
        return \iterator_to_array(new Paginator($query));
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
        $query = $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->leftJoin('d.vulnerabilities', 'v')->addSelect('v')
            ->where('d.projectId = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('d.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        /** @var list<Dependency> */
        return \iterator_to_array(new Paginator($query));
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

    public function findByNameManagerAndProjectId(string $name, string $packageManager, Uuid $projectId): ?Dependency
    {
        /** @var Dependency|null */
        return $this->entityManager->getRepository(Dependency::class)
            ->createQueryBuilder('d')
            ->where('d.name = :name')
            ->andWhere('d.packageManager = :pm')
            ->andWhere('d.projectId = :projectId')
            ->setParameter('name', $name)
            ->setParameter('pm', $packageManager)
            ->setParameter('projectId', $projectId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
        $qb->leftJoin('d.vulnerabilities', 'v')->addSelect('v');
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

        $query = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        /** @var list<Dependency> */
        return \iterator_to_array(new Paginator($query));
    }

    /** @param array{projectId?: string, search?: string, packageManager?: string, type?: string, isOutdated?: bool} $filters */
    public function countFiltered(array $filters = []): int
    {
        $qb = $this->entityManager->getRepository(Dependency::class)->createQueryBuilder('d');
        $qb->select('COUNT(d.id)');
        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @param array{projectId?: string, search?: string, packageManager?: string, type?: string, isOutdated?: bool, sort?: string, sortDir?: string} $filters */
    public function findFilteredWithVersionDates(int $page, int $perPage, array $filters = []): array
    {
        $rsm = new ResultSetMappingBuilder($this->entityManager);
        $rsm->addRootEntityFromClassMetadata(Dependency::class, 'd');
        $rsm->addScalarResult('current_version_released_at', 'currentVersionReleasedAt');
        $rsm->addScalarResult('latest_version_released_at', 'latestVersionReleasedAt');

        $where = $this->buildNativeSqlWhere($filters);
        $params = $this->buildNativeSqlParams($filters);

        $sort = $filters['sort'] ?? 'name';
        $sortDir = \strtoupper($filters['sortDir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = match ($sort) {
            'currentVersion' => 'd.current_version',
            'packageManager' => 'd.package_manager',
            'type' => 'd.type',
            'isOutdated' => 'd.is_outdated',
            default => 'd.name',
        };

        $offset = ($page - 1) * $perPage;

        $sql = <<<SQL
            SELECT {$rsm->generateSelectClause(['d' => 'd'])},
                cv.release_date AS current_version_released_at,
                lv.release_date AS latest_version_released_at
            FROM dependencies d
            LEFT JOIN dependency_versions cv
                ON cv.dependency_name = d.name
                AND cv.package_manager = d.package_manager
                AND cv.version = d.current_version
            LEFT JOIN dependency_versions lv
                ON lv.dependency_name = d.name
                AND lv.package_manager = d.package_manager
                AND lv.is_latest = true
            {$where}
            ORDER BY {$orderBy} {$sortDir}
            LIMIT :limit OFFSET :offset
            SQL;

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        /** @var list<array{0: Dependency, currentVersionReleasedAt: string|null, latestVersionReleasedAt: string|null}> $rows */
        $rows = $this->entityManager->createNativeQuery($sql, $rsm)->setParameters($params)->getResult();

        $result = [];
        foreach ($rows as $row) {
            $dep = $row[0];
            $currentRaw = $row['currentVersionReleasedAt'];
            $latestRaw = $row['latestVersionReleasedAt'];

            $result[] = [
                'dependency' => $dep,
                'currentVersionReleasedAt' => $currentRaw !== null ? (new DateTimeImmutable($currentRaw))->format(DateTimeInterface::ATOM) : null,
                'latestVersionReleasedAt' => $latestRaw !== null ? (new DateTimeImmutable($latestRaw))->format(DateTimeInterface::ATOM) : null,
            ];
        }

        return $result;
    }

    /** @param array{projectId?: string, packageManager?: string, type?: string} $filters */
    public function getStatsSingle(array $filters = []): array
    {
        $where = $this->buildNativeSqlWhere($filters);
        $params = $this->buildNativeSqlParams($filters);

        $vulnsWhere = $where !== ''
            ? \str_replace(['WHERE ', ' d.'], ['WHERE v2.dependency_id = d2.id AND ', ' d2.'], $where)
            : 'WHERE v2.dependency_id = d2.id';

        $sql = <<<SQL
            SELECT
                COUNT(DISTINCT d.id) AS total,
                COUNT(DISTINCT CASE WHEN d.is_outdated = true THEN d.id END) AS outdated,
                (
                    SELECT COUNT(*)
                    FROM vulnerabilities v2
                    INNER JOIN dependencies d2 ON v2.dependency_id = d2.id
                    {$vulnsWhere}
                ) AS total_vulnerabilities
            FROM dependencies d
            {$where}
            SQL;

        /** @var array<string, mixed> $params */
        /** @var array{total: string, outdated: string, total_vulnerabilities: string} $row */
        $row = $this->entityManager->getConnection()->fetchAssociative($sql, $params);

        return [
            'total' => (int) $row['total'],
            'outdated' => (int) $row['outdated'],
            'totalVulnerabilities' => (int) $row['total_vulnerabilities'],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function buildNativeSqlWhere(array $filters): string
    {
        $conditions = [];

        if (isset($filters['projectId']) && $filters['projectId'] !== '') {
            $conditions[] = 'd.project_id = :projectId';
        }
        if (isset($filters['search']) && \is_string($filters['search']) && $filters['search'] !== '') {
            $conditions[] = 'd.name LIKE :search';
        }
        if (isset($filters['packageManager']) && $filters['packageManager'] !== '') {
            $conditions[] = 'd.package_manager = :packageManager';
        }
        if (isset($filters['type']) && $filters['type'] !== '') {
            $conditions[] = 'd.type = :type';
        }
        if (isset($filters['isOutdated'])) {
            $conditions[] = 'd.is_outdated = :isOutdated';
        }

        return $conditions !== [] ? 'WHERE ' . \implode(' AND ', $conditions) : '';
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildNativeSqlParams(array $filters): array
    {
        $params = [];

        if (isset($filters['projectId']) && $filters['projectId'] !== '') {
            $params['projectId'] = $filters['projectId'];
        }
        if (isset($filters['search']) && \is_string($filters['search']) && $filters['search'] !== '') {
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['packageManager']) && $filters['packageManager'] !== '') {
            $params['packageManager'] = $filters['packageManager'];
        }
        if (isset($filters['type']) && $filters['type'] !== '') {
            $params['type'] = $filters['type'];
        }
        if (isset($filters['isOutdated'])) {
            $params['isOutdated'] = $filters['isOutdated'] ? 'true' : 'false';
        }

        return $params;
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['projectId']) && $filters['projectId'] !== '') {
            $qb->andWhere('d.projectId = :projectId')
                ->setParameter('projectId', $filters['projectId']);
        }
        if (isset($filters['search']) && \is_string($filters['search']) && $filters['search'] !== '') {
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
            ->leftJoin('d.vulnerabilities', 'v')->addSelect('v')
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

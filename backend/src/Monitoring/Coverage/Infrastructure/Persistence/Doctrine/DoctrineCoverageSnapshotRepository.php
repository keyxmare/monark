<?php

declare(strict_types=1);

namespace App\Monitoring\Coverage\Infrastructure\Persistence\Doctrine;

use App\Monitoring\Coverage\Domain\Model\CoverageSnapshot;
use App\Monitoring\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineCoverageSnapshotRepository implements CoverageSnapshotRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function save(CoverageSnapshot $snapshot): void
    {
        $this->em->persist($snapshot);
        $this->em->flush();
    }

    public function findLatestByProject(Uuid $projectId): ?CoverageSnapshot
    {
        /** @var CoverageSnapshot|null */
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.projectId = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<CoverageSnapshot> */
    public function findAllByProject(Uuid $projectId, int $limit = 50): array
    {
        /** @var list<CoverageSnapshot> */
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.projectId = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<CoverageSnapshot> */
    public function findLatestPerProject(): array
    {
        $conn = $this->em->getConnection();
        $sql = <<<'SQL'
            SELECT cs.id
            FROM coverage_snapshots cs
            INNER JOIN (
                SELECT project_id, MAX(created_at) AS max_created
                FROM coverage_snapshots
                GROUP BY project_id
            ) latest ON cs.project_id = latest.project_id AND cs.created_at = latest.max_created
            ORDER BY cs.coverage_percent DESC
            SQL;

        $ids = $conn->fetchFirstColumn($sql);
        if ($ids === []) {
            return [];
        }

        /** @var list<CoverageSnapshot> */
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('s.coveragePercent', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<Uuid> $projectIds
     *
     * @return array<string, list<array{name: string, percent: float}>>
     */
    public function findLatestJobsForProjects(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $conn = $this->em->getConnection();
        $sql = <<<'SQL'
            SELECT cs.project_id::text AS project_id, cs.jobs
            FROM coverage_snapshots cs
            INNER JOIN (
                SELECT project_id, MAX(created_at) AS max_created
                FROM coverage_snapshots
                WHERE project_id::text IN (:ids)
                GROUP BY project_id
            ) latest ON cs.project_id = latest.project_id AND cs.created_at = latest.max_created
            SQL;

        $bytes = \array_map(static fn (Uuid $id): string => $id->toRfc4122(), $projectIds);
        /** @var list<array{project_id: string, jobs: ?string}> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['ids' => $bytes], ['ids' => \Doctrine\DBAL\ArrayParameterType::STRING]);

        $out = [];
        foreach ($rows as $row) {
            if ($row['jobs'] === null || $row['jobs'] === '') {
                continue;
            }
            $decoded = \json_decode($row['jobs'], true);
            if (!\is_array($decoded) || $decoded === []) {
                continue;
            }
            $items = [];
            foreach ($decoded as $entry) {
                if (
                    \is_array($entry)
                    && isset($entry['name'], $entry['percent'])
                    && \is_string($entry['name'])
                    && (\is_float($entry['percent']) || \is_int($entry['percent']) || \is_string($entry['percent']))
                ) {
                    $items[] = ['name' => $entry['name'], 'percent' => (float) $entry['percent']];
                }
            }
            if ($items !== []) {
                $out[$row['project_id']] = $items;
            }
        }

        return $out;
    }

    /**
     * @param list<Uuid> $projectIds
     *
     * @return array<string, float>
     */
    public function findLatestPercentForProjects(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $conn = $this->em->getConnection();
        $sql = <<<'SQL'
            SELECT cs.project_id::text AS project_id, cs.coverage_percent
            FROM coverage_snapshots cs
            INNER JOIN (
                SELECT project_id, MAX(created_at) AS max_created
                FROM coverage_snapshots
                WHERE project_id::text IN (:ids)
                GROUP BY project_id
            ) latest ON cs.project_id = latest.project_id AND cs.created_at = latest.max_created
            SQL;

        $bytes = \array_map(static fn (Uuid $id): string => $id->toRfc4122(), $projectIds);
        /** @var list<array{project_id: string, coverage_percent: float|string}> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['ids' => $bytes], ['ids' => \Doctrine\DBAL\ArrayParameterType::STRING]);

        $out = [];
        foreach ($rows as $row) {
            $out[$row['project_id']] = (float) $row['coverage_percent'];
        }

        return $out;
    }

    /** @return list<CoverageSnapshot> */
    public function findPreviousPerProject(): array
    {
        $conn = $this->em->getConnection();
        $sql = <<<'SQL'
            SELECT cs.id
            FROM coverage_snapshots cs
            INNER JOIN (
                SELECT project_id, MAX(created_at) AS max_created
                FROM coverage_snapshots
                WHERE (project_id, created_at) NOT IN (
                    SELECT project_id, MAX(created_at)
                    FROM coverage_snapshots
                    GROUP BY project_id
                )
                GROUP BY project_id
            ) prev ON cs.project_id = prev.project_id AND cs.created_at = prev.max_created
            SQL;

        $ids = $conn->fetchFirstColumn($sql);
        if ($ids === []) {
            return [];
        }

        /** @var list<CoverageSnapshot> */
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}

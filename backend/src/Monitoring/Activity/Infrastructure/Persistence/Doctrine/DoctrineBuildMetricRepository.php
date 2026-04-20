<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Infrastructure\Persistence\Doctrine;

use App\Monitoring\Activity\Domain\Model\BuildMetric;
use App\Monitoring\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
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

    public function summarizeForProjects(array $projectIds, int $windowDays): array
    {
        if ($projectIds === [] || $windowDays <= 0) {
            return [];
        }

        $utc = new DateTimeZone('UTC');
        $now = new DateTimeImmutable('now', $utc);
        $since = $now->modify(\sprintf('-%d days', $windowDays - 1))->setTime(0, 0, 0);

        $ids = \array_map(static fn (Uuid $id): string => $id->toRfc4122(), $projectIds);

        $conn = $this->entityManager->getConnection();

        $dailySql = <<<'SQL'
            SELECT project_id::text AS project_id,
                   TO_CHAR(DATE_TRUNC('day', created_at AT TIME ZONE 'UTC'), 'YYYY-MM-DD') AS day,
                   COUNT(DISTINCT commit_sha) AS c
            FROM activity_build_metrics
            WHERE project_id::text IN (:ids)
              AND created_at >= :since
            GROUP BY project_id, day
            SQL;

        /** @var list<array{project_id: string, day: string, c: int|string}> $dailyRows */
        $dailyRows = $conn->fetchAllAssociative(
            $dailySql,
            ['ids' => $ids, 'since' => $since->format('Y-m-d H:i:s')],
            ['ids' => ArrayParameterType::STRING],
        );

        $latestSql = <<<'SQL'
            SELECT DISTINCT ON (bm.project_id)
                   bm.project_id::text AS project_id,
                   bm.commit_sha,
                   bm.created_at
            FROM activity_build_metrics bm
            WHERE bm.project_id::text IN (:ids)
            ORDER BY bm.project_id, bm.created_at DESC
            SQL;

        /** @var list<array{project_id: string, commit_sha: string, created_at: string}> $latestRows */
        $latestRows = $conn->fetchAllAssociative(
            $latestSql,
            ['ids' => $ids],
            ['ids' => ArrayParameterType::STRING],
        );

        $buckets = [];
        foreach ($dailyRows as $row) {
            $buckets[$row['project_id']][$row['day']] = (int) $row['c'];
        }

        $result = [];
        foreach ($latestRows as $row) {
            $projectId = $row['project_id'];
            $daily = [];
            $total = 0;
            for ($i = $windowDays - 1; $i >= 0; --$i) {
                $dayKey = $now->modify(\sprintf('-%d days', $i))->format('Y-m-d');
                $c = $buckets[$projectId][$dayKey] ?? 0;
                $daily[] = $c;
                $total += $c;
            }

            $result[$projectId] = [
                'total' => $total,
                'lastAt' => new DateTimeImmutable($row['created_at'], $utc),
                'lastSha' => $row['commit_sha'],
                'daily' => $daily,
            ];
        }

        return $result;
    }
}

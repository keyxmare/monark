<?php

declare(strict_types=1);

namespace App\Coverage\Infrastructure\Persistence\Doctrine;

use App\Coverage\Domain\Model\CoverageSnapshot;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
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

        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('s.coveragePercent', 'DESC')
            ->getQuery()
            ->getResult();
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

        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Monitoring\Coverage\Infrastructure;

use App\Hub\Shared\Domain\Port\WeakestCoverageProjectsInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DoctrineWeakestCoverageProjects implements WeakestCoverageProjectsInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function lowest(int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT p.id::text AS id, p.name, p.slug, cs.coverage_percent
            FROM catalog_projects p
            INNER JOIN (
                SELECT project_id, MAX(created_at) AS max_created
                FROM coverage_snapshots
                GROUP BY project_id
            ) latest ON latest.project_id = p.id
            INNER JOIN coverage_snapshots cs
                ON cs.project_id = latest.project_id AND cs.created_at = latest.max_created
            ORDER BY cs.coverage_percent ASC
            LIMIT :lim
            SQL;

        /** @var list<array{id: string, name: string, slug: string, coverage_percent: float|string}> $rows */
        $rows = $this->connection->fetchAllAssociative($sql, ['lim' => $limit], ['lim' => ParameterType::INTEGER]);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'coveragePercent' => (float) $row['coverage_percent'],
            ];
        }

        return $out;
    }
}

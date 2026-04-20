<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Infrastructure;

use App\Hub\Shared\Domain\Port\ProjectDriftSummaryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DoctrineProjectDriftSummary implements ProjectDriftSummaryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function byProject(int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT p.name AS name, p.slug AS slug,
                   COUNT(f.id) AS total,
                   SUM(CASE WHEN f.latest_lts IS NULL OR f.version = f.latest_lts THEN 1 ELSE 0 END) AS up_to_date
            FROM catalog_projects p
            INNER JOIN catalog_frameworks f ON f.project_id = p.id
            GROUP BY p.id, p.name, p.slug
            HAVING COUNT(f.id) > 0
            ORDER BY (COUNT(f.id) - SUM(CASE WHEN f.latest_lts IS NULL OR f.version = f.latest_lts THEN 1 ELSE 0 END)) DESC,
                     p.name ASC
            LIMIT :lim
            SQL;

        /** @var list<array{name: string, slug: string, total: int|string, up_to_date: int|string}> $rows */
        $rows = $this->connection->fetchAllAssociative($sql, ['lim' => $limit], ['lim' => ParameterType::INTEGER]);

        $out = [];
        foreach ($rows as $row) {
            $total = (int) $row['total'];
            $upToDate = (int) $row['up_to_date'];
            $out[] = [
                'name' => $row['name'],
                'slug' => $row['slug'],
                'total' => $total,
                'upToDate' => $upToDate,
                'drift' => $total - $upToDate,
            ];
        }

        return $out;
    }
}

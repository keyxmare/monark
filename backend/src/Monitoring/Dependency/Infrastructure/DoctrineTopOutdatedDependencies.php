<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Infrastructure;

use App\Hub\Shared\Domain\Port\TopOutdatedDependenciesInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DoctrineTopOutdatedDependencies implements TopOutdatedDependenciesInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function top(int $limit, int $occurrencesLimit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $nameRowsSql = <<<'SQL'
            SELECT d.name AS name, COUNT(DISTINCT d.project_id) AS projects_count
            FROM dependencies d
            WHERE d.is_outdated = TRUE
            GROUP BY d.name
            ORDER BY projects_count DESC, d.name ASC
            LIMIT :lim
            SQL;

        /** @var list<array{name: string, projects_count: int|string}> $nameRows */
        $nameRows = $this->connection->fetchAllAssociative($nameRowsSql, ['lim' => $limit], ['lim' => ParameterType::INTEGER]);

        if ($nameRows === []) {
            return [];
        }

        $names = \array_map(static fn (array $row): string => $row['name'], $nameRows);

        $occSql = <<<'SQL'
            SELECT d.name AS name, p.name AS project_name, p.slug AS project_slug,
                   d.current_version, d.latest_version
            FROM dependencies d
            INNER JOIN catalog_projects p ON p.id = d.project_id
            WHERE d.is_outdated = TRUE AND d.name IN (:names)
            ORDER BY d.name ASC, p.name ASC
            SQL;

        /** @var list<array{name: string, project_name: string, project_slug: string, current_version: string, latest_version: string}> $occRows */
        $occRows = $this->connection->fetchAllAssociative(
            $occSql,
            ['names' => $names],
            ['names' => \Doctrine\DBAL\ArrayParameterType::STRING],
        );

        $occurrencesByName = [];
        foreach ($occRows as $row) {
            $occurrencesByName[$row['name']] ??= [];
            $occurrencesByName[$row['name']][] = [
                'projectName' => $row['project_name'],
                'projectSlug' => $row['project_slug'],
                'currentVersion' => $row['current_version'],
                'latestVersion' => $row['latest_version'],
            ];
        }

        $out = [];
        foreach ($nameRows as $row) {
            $out[] = [
                'name' => $row['name'],
                'projectsCount' => (int) $row['projects_count'],
                'occurrences' => \array_slice($occurrencesByName[$row['name']] ?? [], 0, $occurrencesLimit),
            ];
        }

        return $out;
    }
}

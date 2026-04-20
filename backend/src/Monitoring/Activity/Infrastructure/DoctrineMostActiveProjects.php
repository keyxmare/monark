<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Infrastructure;

use App\Hub\Shared\Domain\Port\MostActiveProjectsInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineMostActiveProjects implements MostActiveProjectsInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function top(int $limit, int $windowDays): array
    {
        if ($limit <= 0 || $windowDays <= 0) {
            return [];
        }

        /** @var list<array{id: string, name: string, slug: string, commits_daily_series: string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id::text AS id, name, slug, commits_daily_series FROM catalog_projects'
        );

        $scored = [];
        foreach ($rows as $row) {
            $series = \json_decode($row['commits_daily_series'], true);
            $commits = 0;
            if (\is_array($series)) {
                foreach (\array_slice($series, -$windowDays) as $value) {
                    if (\is_int($value) || \is_float($value)) {
                        $commits += (int) $value;
                    }
                }
            }
            $scored[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'commits' => $commits,
            ];
        }

        \usort($scored, static fn (array $a, array $b): int => $b['commits'] <=> $a['commits']);

        return \array_values(\array_filter(
            \array_slice($scored, 0, $limit),
            static fn (array $p): bool => $p['commits'] > 0,
        ));
    }
}

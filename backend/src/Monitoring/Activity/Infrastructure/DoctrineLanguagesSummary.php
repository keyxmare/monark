<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Infrastructure;

use App\Hub\Shared\Domain\Port\LanguagesSummaryInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineLanguagesSummary implements LanguagesSummaryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function topLanguages(int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        /** @var list<array{languages_palette: string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT languages_palette FROM catalog_projects'
        );

        $counts = [];
        foreach ($rows as $row) {
            $palette = \json_decode($row['languages_palette'], true);
            if (!\is_array($palette)) {
                continue;
            }
            foreach ($palette as $name => $bytes) {
                if (!\is_string($name) || $name === '' || !\is_numeric($bytes) || (int) $bytes <= 0) {
                    continue;
                }
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        \arsort($counts);

        $top = [];
        foreach (\array_slice($counts, 0, $limit, true) as $name => $count) {
            $top[] = ['name' => $name, 'projects_count' => $count];
        }

        return $top;
    }
}

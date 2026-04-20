<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Infrastructure;

use App\Hub\Shared\Domain\Port\CommitCounterInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineCommitCounter implements CommitCounterInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function countDistinctSince(int $days): int
    {
        if ($days <= 0) {
            return 0;
        }

        /** @var list<array{commits_daily_series: string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT commits_daily_series FROM catalog_projects'
        );

        $total = 0;
        foreach ($rows as $row) {
            $series = \json_decode($row['commits_daily_series'], true);
            if (!\is_array($series)) {
                continue;
            }
            $total += (int) \array_sum(\array_slice($series, -$days));
        }

        return $total;
    }
}

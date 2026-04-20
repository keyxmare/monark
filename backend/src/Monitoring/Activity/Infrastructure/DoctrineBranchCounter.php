<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Infrastructure;

use App\Hub\Shared\Domain\Port\BranchCounterInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineBranchCounter implements BranchCounterInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function countActiveSince(int $days): int
    {
        if ($days <= 0) {
            return 0;
        }

        $sql = <<<'SQL'
            SELECT COUNT(DISTINCT (project_id, ref)) AS c
            FROM activity_build_metrics
            WHERE created_at >= NOW() - make_interval(days => :days)
            SQL;

        /** @var int|string|false $value */
        $value = $this->connection->fetchOne($sql, ['days' => $days]);

        return (int) $value;
    }
}

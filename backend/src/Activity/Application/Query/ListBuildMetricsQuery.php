<?php

declare(strict_types=1);

namespace App\Activity\Application\Query;

final readonly class ListBuildMetricsQuery
{
    public function __construct(
        public string $projectId,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}

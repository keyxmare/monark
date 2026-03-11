<?php

declare(strict_types=1);

namespace App\Activity\Application\Query;

final readonly class ListSyncTasksQuery
{
    public function __construct(
        public ?string $status = null,
        public ?string $type = null,
        public ?string $severity = null,
        public ?string $projectId = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}

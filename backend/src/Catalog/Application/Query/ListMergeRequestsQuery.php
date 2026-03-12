<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

final readonly class ListMergeRequestsQuery
{
    public function __construct(
        public string $projectId,
        public int $page = 1,
        public int $perPage = 20,
        public ?string $status = null,
        public ?string $author = null,
    ) {
    }
}

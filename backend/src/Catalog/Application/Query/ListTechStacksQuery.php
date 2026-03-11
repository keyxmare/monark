<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

final readonly class ListTechStacksQuery
{
    public function __construct(
        public ?string $projectId = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}

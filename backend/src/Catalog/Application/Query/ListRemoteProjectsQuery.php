<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

final readonly class ListRemoteProjectsQuery
{
    public function __construct(
        public string $providerId,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}

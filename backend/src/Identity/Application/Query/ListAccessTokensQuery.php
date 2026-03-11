<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

final readonly class ListAccessTokensQuery
{
    public function __construct(
        public string $userId,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}

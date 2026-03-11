<?php

declare(strict_types=1);

namespace App\Activity\Application\Query;

final readonly class ListActivityEventsQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}

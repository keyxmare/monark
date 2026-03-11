<?php

declare(strict_types=1);

namespace App\Activity\Application\Query;

final readonly class ListNotificationsQuery
{
    public function __construct(
        public string $userId,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}

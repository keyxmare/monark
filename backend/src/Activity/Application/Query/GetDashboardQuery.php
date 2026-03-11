<?php

declare(strict_types=1);

namespace App\Activity\Application\Query;

final readonly class GetDashboardQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}

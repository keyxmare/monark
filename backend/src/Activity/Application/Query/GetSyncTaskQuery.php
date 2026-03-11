<?php

declare(strict_types=1);

namespace App\Activity\Application\Query;

final readonly class GetSyncTaskQuery
{
    public function __construct(
        public string $id,
    ) {
    }
}

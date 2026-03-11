<?php

declare(strict_types=1);

namespace App\Dependency\Application\Query;

final readonly class ListDependenciesQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Dependency\Application\Query;

final readonly class ListDependenciesQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $projectId = null,
        public ?string $search = null,
        public ?string $packageManager = null,
        public ?string $type = null,
        public ?bool $isOutdated = null,
        public string $sort = 'name',
        public string $sortDir = 'asc',
    ) {
    }
}

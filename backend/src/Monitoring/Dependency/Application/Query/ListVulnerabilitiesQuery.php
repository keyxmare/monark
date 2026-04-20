<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\Query;

final readonly class ListVulnerabilitiesQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $dependencyId = null,
    ) {
    }
}

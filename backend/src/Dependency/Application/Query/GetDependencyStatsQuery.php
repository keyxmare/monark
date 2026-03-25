<?php

declare(strict_types=1);

namespace App\Dependency\Application\Query;

final readonly class GetDependencyStatsQuery
{
    public function __construct(
        public ?string $projectId = null,
        public ?string $packageManager = null,
        public ?string $type = null,
    ) {
    }
}

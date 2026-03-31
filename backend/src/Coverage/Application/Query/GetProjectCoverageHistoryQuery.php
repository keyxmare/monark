<?php

declare(strict_types=1);

namespace App\Coverage\Application\Query;

final readonly class GetProjectCoverageHistoryQuery
{
    public function __construct(
        public string $projectSlug,
    ) {}
}

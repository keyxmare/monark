<?php

declare(strict_types=1);

namespace App\Dependency\Application\Query;

final readonly class GetDependencyQuery
{
    public function __construct(
        public string $dependencyId,
    ) {
    }
}

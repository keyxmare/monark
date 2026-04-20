<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\DTO;

use App\Hub\Shared\Application\DTO\PaginatedOutput;

final readonly class DependencyListOutput
{
    public function __construct(
        public PaginatedOutput $pagination,
    ) {
    }
}

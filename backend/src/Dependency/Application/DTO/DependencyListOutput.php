<?php

declare(strict_types=1);

namespace App\Dependency\Application\DTO;

use App\Shared\Application\DTO\PaginatedOutput;

final readonly class DependencyListOutput
{
    public function __construct(
        public PaginatedOutput $pagination,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Shared\Application\DTO\PaginatedOutput;

final readonly class TechStackListOutput
{
    public function __construct(
        public PaginatedOutput $pagination,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\DTO;

use App\Hub\Shared\Application\DTO\PaginatedOutput;

final readonly class ProviderListOutput
{
    public function __construct(
        public PaginatedOutput $pagination,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use App\Shared\Application\DTO\PaginatedOutput;

final readonly class AccessTokenListOutput
{
    public function __construct(
        public PaginatedOutput $pagination,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Hub\Identity\Application\DTO;

use App\Hub\Shared\Application\DTO\PaginatedOutput;

final readonly class UserListOutput
{
    public function __construct(
        public PaginatedOutput $pagination,
    ) {
    }
}

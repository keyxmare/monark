<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use App\Shared\Application\DTO\PaginatedOutput;

final readonly class AttemptListOutput
{
    public function __construct(
        public PaginatedOutput $pagination,
    ) {
    }
}

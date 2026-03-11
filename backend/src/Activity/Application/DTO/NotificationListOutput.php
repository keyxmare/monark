<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

use App\Shared\Application\DTO\PaginatedOutput;

final readonly class NotificationListOutput
{
    public function __construct(
        public PaginatedOutput $pagination,
    ) {
    }
}

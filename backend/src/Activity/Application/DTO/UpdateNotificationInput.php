<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class UpdateNotificationInput
{
    public function __construct(
        public ?string $readAt = null,
    ) {
    }
}

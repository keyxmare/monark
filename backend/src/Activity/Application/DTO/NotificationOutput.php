<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class NotificationOutput
{
    public function __construct(
        public string $id,
        public string $title,
        public string $message,
        public string $channel,
        public ?string $readAt,
        public string $userId,
        public string $createdAt,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Activity\Application\Command;

final readonly class MarkNotificationReadCommand
{
    public function __construct(
        public string $notificationId,
    ) {
    }
}

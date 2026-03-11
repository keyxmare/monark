<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
}

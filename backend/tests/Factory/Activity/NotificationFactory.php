<?php

declare(strict_types=1);

namespace App\Tests\Factory\Activity;

use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;

final class NotificationFactory
{
    public static function create(array $overrides = []): Notification
    {
        return Notification::create(
            title: $overrides['title'] ?? 'Test Notification',
            message: $overrides['message'] ?? 'This is a test notification.',
            channel: $overrides['channel'] ?? NotificationChannel::InApp,
            userId: $overrides['userId'] ?? '00000000-0000-0000-0000-000000000001',
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Activity\Application\Mapper;

use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Domain\Model\Notification;
use DateTimeInterface;

final class NotificationMapper
{
    public static function toOutput(Notification $notification): NotificationOutput
    {
        return new NotificationOutput(
            id: $notification->getId()->toRfc4122(),
            title: $notification->getTitle(),
            message: $notification->getMessage(),
            channel: $notification->getChannel()->value,
            readAt: $notification->getReadAt()?->format(DateTimeInterface::ATOM),
            userId: $notification->getUserId(),
            createdAt: $notification->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}

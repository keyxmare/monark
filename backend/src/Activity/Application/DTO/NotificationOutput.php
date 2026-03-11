<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

use App\Activity\Domain\Model\Notification;

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

    public static function fromEntity(Notification $notification): self
    {
        return new self(
            id: $notification->getId()->toRfc4122(),
            title: $notification->getTitle(),
            message: $notification->getMessage(),
            channel: $notification->getChannel()->value,
            readAt: $notification->getReadAt()?->format(\DateTimeInterface::ATOM),
            userId: $notification->getUserId(),
            createdAt: $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}

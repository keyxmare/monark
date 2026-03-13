<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

use App\Activity\Domain\Model\ActivityEvent;
use DateTimeInterface;

final readonly class ActivityEventOutput
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public string $type,
        public string $entityType,
        public string $entityId,
        public array $payload,
        public string $occurredAt,
        public string $userId,
    ) {
    }

    public static function fromEntity(ActivityEvent $event): self
    {
        return new self(
            id: $event->getId()->toRfc4122(),
            type: $event->getType(),
            entityType: $event->getEntityType(),
            entityId: $event->getEntityId(),
            payload: $event->getPayload(),
            occurredAt: $event->getOccurredAt()->format(DateTimeInterface::ATOM),
            userId: $event->getUserId(),
        );
    }
}

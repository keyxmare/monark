<?php

declare(strict_types=1);

namespace App\Activity\Application\Mapper;

use App\Activity\Application\DTO\ActivityEventOutput;
use App\Activity\Domain\Model\ActivityEvent;
use DateTimeInterface;

final class ActivityEventMapper
{
    public static function toOutput(ActivityEvent $event): ActivityEventOutput
    {
        return new ActivityEventOutput(
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

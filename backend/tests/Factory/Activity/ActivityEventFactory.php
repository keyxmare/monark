<?php

declare(strict_types=1);

namespace App\Tests\Factory\Activity;

use App\Activity\Domain\Model\ActivityEvent;

final class ActivityEventFactory
{
    public static function create(array $overrides = []): ActivityEvent
    {
        return ActivityEvent::create(
            type: $overrides['type'] ?? 'project.created',
            entityType: $overrides['entityType'] ?? 'Project',
            entityId: $overrides['entityId'] ?? 'abc-123',
            payload: $overrides['payload'] ?? ['name' => 'Test Project'],
            userId: $overrides['userId'] ?? '00000000-0000-0000-0000-000000000001',
        );
    }
}

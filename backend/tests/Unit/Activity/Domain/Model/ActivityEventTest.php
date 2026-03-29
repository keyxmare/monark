<?php

declare(strict_types=1);

use App\Activity\Domain\Model\ActivityEvent;
use App\Tests\Factory\Activity\ActivityEventFactory;
use Symfony\Component\Uid\Uuid;

describe('ActivityEvent', function () {
    it('creates with all fields via factory', function () {
        $event = ActivityEventFactory::create();

        expect($event->getId())->toBeInstanceOf(Uuid::class);
        expect($event->getType())->toBe('project.created');
        expect($event->getEntityType())->toBe('Project');
        expect($event->getEntityId())->toBe('abc-123');
        expect($event->getPayload())->toBe(['name' => 'Test Project']);
        expect($event->getUserId())->toBe('00000000-0000-0000-0000-000000000001');
        expect($event->getOccurredAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates with custom values', function () {
        $event = ActivityEvent::create(
            type: 'dependency.updated',
            entityType: 'Dependency',
            entityId: 'dep-456',
            payload: ['version' => '2.0.0'],
            userId: 'user-42',
        );

        expect($event->getType())->toBe('dependency.updated');
        expect($event->getEntityType())->toBe('Dependency');
        expect($event->getEntityId())->toBe('dep-456');
        expect($event->getPayload())->toBe(['version' => '2.0.0']);
        expect($event->getUserId())->toBe('user-42');
    });

    it('creates with empty payload', function () {
        $event = ActivityEventFactory::create(['payload' => []]);

        expect($event->getPayload())->toBe([]);
    });

    it('generates unique ids', function () {
        $event1 = ActivityEventFactory::create();
        $event2 = ActivityEventFactory::create();

        expect($event1->getId()->equals($event2->getId()))->toBeFalse();
    });

    it('sets occurredAt to current time', function () {
        $before = new \DateTimeImmutable();
        $event = ActivityEventFactory::create();
        $after = new \DateTimeImmutable();

        expect($event->getOccurredAt() >= $before)->toBeTrue();
        expect($event->getOccurredAt() <= $after)->toBeTrue();
    });
});

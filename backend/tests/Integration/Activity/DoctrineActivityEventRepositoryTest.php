<?php

declare(strict_types=1);

use App\Activity\Domain\Model\ActivityEvent;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(ActivityEventRepositoryInterface::class);
});

function createActivityEvent(
    string $type = 'project.created',
    string $entityType = 'Project',
    ?string $entityId = null,
    array $payload = ['key' => 'value'],
    string $userId = 'user-1',
): ActivityEvent {
    return ActivityEvent::create(
        type: $type,
        entityType: $entityType,
        entityId: $entityId ?? Uuid::v7()->toRfc4122(),
        payload: $payload,
        userId: $userId,
    );
}

describe('DoctrineActivityEventRepository', function () {
    it('saves and finds an activity event by id', function () {
        $event = \createActivityEvent();
        $this->repo->save($event);

        $found = $this->repo->findById($event->getId());

        expect($found)->not->toBeNull();
        expect($found->getType())->toBe('project.created');
        expect($found->getEntityType())->toBe('Project');
        expect($found->getPayload())->toBe(['key' => 'value']);
        expect($found->getUserId())->toBe('user-1');
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(Uuid::v7()))->toBeNull();
    });

    it('lists events with pagination', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->repo->save(\createActivityEvent(entityId: Uuid::v7()->toRfc4122()));
        }

        $page1 = $this->repo->findAll(page: 1, perPage: 3);
        expect($page1)->toHaveCount(3);

        $page2 = $this->repo->findAll(page: 2, perPage: 3);
        expect($page2)->toHaveCount(2);
    });

    it('counts events', function () {
        expect($this->repo->count())->toBe(0);

        $this->repo->save(\createActivityEvent());
        $this->repo->save(\createActivityEvent());

        expect($this->repo->count())->toBe(2);
    });
});

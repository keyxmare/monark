<?php

declare(strict_types=1);

use App\Activity\Application\DTO\ActivityEventOutput;
use App\Activity\Application\Query\GetActivityEventQuery;
use App\Activity\Application\QueryHandler\GetActivityEventHandler;
use App\Activity\Domain\Model\ActivityEvent;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetActivityEventRepo(?ActivityEvent $event = null): ActivityEventRepositoryInterface
{
    return new class ($event) implements ActivityEventRepositoryInterface {
        public function __construct(private readonly ?ActivityEvent $event)
        {
        }
        public function findById(Uuid $id): ?ActivityEvent
        {
            return $this->event;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(ActivityEvent $event): void
        {
        }
    };
}

describe('GetActivityEventHandler', function () {
    it('returns an activity event by id', function () {
        $event = ActivityEvent::create(
            type: 'project.created',
            entityType: 'Project',
            entityId: 'abc-123',
            payload: ['name' => 'Test'],
            userId: '00000000-0000-0000-0000-000000000001',
        );

        $handler = new GetActivityEventHandler(\stubGetActivityEventRepo($event));
        $result = $handler(new GetActivityEventQuery($event->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(ActivityEventOutput::class);
        expect($result->type)->toBe('project.created');
        expect($result->entityType)->toBe('Project');
    });

    it('throws not found when event does not exist', function () {
        $handler = new GetActivityEventHandler(\stubGetActivityEventRepo(null));
        $handler(new GetActivityEventQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});

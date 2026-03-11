<?php

declare(strict_types=1);

use App\Activity\Application\DTO\ActivityEventListOutput;
use App\Activity\Application\Query\ListActivityEventsQuery;
use App\Activity\Application\QueryHandler\ListActivityEventsHandler;
use App\Activity\Domain\Model\ActivityEvent;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListActivityEventsRepo(array $events = [], int $count = 0): ActivityEventRepositoryInterface
{
    return new class ($events, $count) implements ActivityEventRepositoryInterface {
        public function __construct(private readonly array $events, private readonly int $count) {}
        public function findById(Uuid $id): ?ActivityEvent { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return $this->events; }
        public function count(): int { return $this->count; }
        public function save(ActivityEvent $event): void {}
    };
}

describe('ListActivityEventsHandler', function () {
    it('returns paginated activity events', function () {
        $event = ActivityEvent::create(
            type: 'project.created',
            entityType: 'Project',
            entityId: 'abc-123',
            payload: [],
            userId: '00000000-0000-0000-0000-000000000001',
        );

        $handler = new ListActivityEventsHandler(stubListActivityEventsRepo([$event], 1));
        $result = $handler(new ListActivityEventsQuery());

        expect($result)->toBeInstanceOf(ActivityEventListOutput::class);
        expect($result->pagination->items)->toHaveCount(1);
        expect($result->pagination->total)->toBe(1);
    });

    it('returns empty list when no events', function () {
        $handler = new ListActivityEventsHandler(stubListActivityEventsRepo([], 0));
        $result = $handler(new ListActivityEventsQuery());

        expect($result->pagination->items)->toHaveCount(0);
        expect($result->pagination->total)->toBe(0);
    });
});

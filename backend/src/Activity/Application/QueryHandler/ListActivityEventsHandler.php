<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\ActivityEventListOutput;
use App\Activity\Application\DTO\ActivityEventOutput;
use App\Activity\Application\Query\ListActivityEventsQuery;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListActivityEventsHandler
{
    public function __construct(
        private ActivityEventRepositoryInterface $activityEventRepository,
    ) {
    }

    public function __invoke(ListActivityEventsQuery $query): ActivityEventListOutput
    {
        $events = $this->activityEventRepository->findAll($query->page, $query->perPage);
        $total = $this->activityEventRepository->count();

        $items = \array_map(
            static fn (mixed $event) => ActivityEventOutput::fromEntity($event),
            $events,
        );

        return new ActivityEventListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}

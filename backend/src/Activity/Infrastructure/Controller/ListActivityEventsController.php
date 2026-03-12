<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Controller;

use App\Activity\Application\DTO\ActivityEventListOutput;
use App\Activity\Application\Query\ListActivityEventsQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/events', name: 'activity_events_list', methods: ['GET'])]
final readonly class ListActivityEventsController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);

        $envelope = $this->queryBus->dispatch(new ListActivityEventsQuery($page, $perPage));
        /** @var ActivityEventListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}

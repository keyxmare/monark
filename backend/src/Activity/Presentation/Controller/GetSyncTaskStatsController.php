<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Query\GetSyncTaskStatsQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/sync-tasks/stats', name: 'activity_sync_tasks_stats', methods: ['GET'])]
final readonly class GetSyncTaskStatsController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetSyncTaskStatsQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->toArray())->toArray());
    }
}

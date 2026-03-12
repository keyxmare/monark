<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\DTO\SyncTaskListOutput;
use App\Activity\Application\Query\ListSyncTasksQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/sync-tasks', name: 'activity_sync_tasks_list', methods: ['GET'])]
final readonly class ListSyncTasksController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(
            new ListSyncTasksQuery(
                status: $request->query->get('status'),
                type: $request->query->get('type'),
                severity: $request->query->get('severity'),
                projectId: $request->query->get('project_id'),
                page: $request->query->getInt('page', 1),
                perPage: $request->query->getInt('per_page', 20),
            ),
        );
        /** @var SyncTaskListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}

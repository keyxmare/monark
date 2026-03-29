<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\DTO\SyncTaskListOutput;
use App\Activity\Application\Query\ListSyncTasksQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/sync-tasks', name: 'activity_sync_tasks_list', methods: ['GET'])]
#[OA\Get(
    summary: 'List sync tasks',
    tags: ['Activity / Sync Tasks'],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'severity', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'project_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
    ],
    responses: [new OA\Response(response: 200, description: 'Paginated list of sync tasks')],
)]
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

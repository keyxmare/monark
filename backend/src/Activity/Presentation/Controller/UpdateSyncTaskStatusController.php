<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Command\UpdateSyncTaskStatusCommand;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/v1/activity/sync-tasks/{id}', name: 'activity_sync_tasks_update', methods: ['PATCH'], requirements: ['id' => Requirement::UUID_V7])]
#[OA\Patch(
    summary: 'Update a sync task status',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [new OA\Property(property: 'status', type: 'string')],
        ),
    ),
    tags: ['Activity / Sync Tasks'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'Sync task updated'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class UpdateSyncTaskStatusController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        /** @var array{status?: string} $data */
        $data = \json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $envelope = $this->commandBus->dispatch(
            new UpdateSyncTaskStatusCommand($id, $data['status'] ?? ''),
        );
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}

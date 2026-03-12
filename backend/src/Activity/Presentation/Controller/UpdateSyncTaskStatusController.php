<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Command\UpdateSyncTaskStatusCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/sync-tasks/{id}', name: 'activity_sync_tasks_update', methods: ['PATCH'])]
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

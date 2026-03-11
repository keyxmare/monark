<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Controller;

use App\Activity\Application\Command\MarkNotificationReadCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/notifications/{id}', name: 'activity_notifications_update', methods: ['PUT'])]
final readonly class UpdateNotificationController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new MarkNotificationReadCommand($id));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}

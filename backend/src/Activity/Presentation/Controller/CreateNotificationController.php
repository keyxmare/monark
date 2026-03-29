<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Command\CreateNotificationCommand;
use App\Activity\Application\DTO\CreateNotificationInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/notifications', name: 'activity_notifications_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a notification',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateNotificationInput::class)),
    ),
    tags: ['Activity / Notifications'],
    responses: [
        new OA\Response(response: 201, description: 'Notification created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateNotificationController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateNotificationInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateNotificationCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

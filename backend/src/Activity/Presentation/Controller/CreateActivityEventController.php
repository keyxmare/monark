<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Command\CreateActivityEventCommand;
use App\Activity\Application\DTO\CreateActivityEventInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/activity/events', name: 'activity_events_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create an activity event',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateActivityEventInput::class)),
    ),
    tags: ['Activity / Events'],
    responses: [
        new OA\Response(response: 201, description: 'Event created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateActivityEventController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateActivityEventInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateActivityEventCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

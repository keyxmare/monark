<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Query\GetMessengerStatsQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/activity/messenger/stats', name: 'activity_messenger_stats', methods: ['GET'])]
#[OA\Get(
    summary: 'Get messenger queue statistics',
    tags: ['Activity / Sync Tasks'],
    responses: [new OA\Response(response: 200, description: 'Messenger statistics')],
)]
final readonly class GetMessengerStatsController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetMessengerStatsQuery());
        /** @var \App\Activity\Application\DTO\MessengerStatsOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->toArray())->toArray());
    }
}

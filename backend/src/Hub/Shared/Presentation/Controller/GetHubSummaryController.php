<?php

declare(strict_types=1);

namespace App\Hub\Shared\Presentation\Controller;

use App\Hub\Shared\Application\DTO\ApiResponse;
use App\Hub\Shared\Application\DTO\HubSummaryOutput;
use App\Hub\Shared\Application\Query\GetHubSummaryQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/summary', name: 'hub_summary', methods: ['GET'])]
#[OA\Get(
    summary: 'Hub home KPI summary (apps active, repos tracked, commits 30d, focus seconds today)',
    tags: ['Hub'],
    responses: [new OA\Response(response: 200, description: 'Hub summary KPIs')],
)]
final readonly class GetHubSummaryController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetHubSummaryQuery());
        /** @var HubSummaryOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->toArray())->toArray());
    }
}

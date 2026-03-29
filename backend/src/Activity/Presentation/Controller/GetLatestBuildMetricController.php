<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Query\GetLatestBuildMetricQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/projects/{projectId}/build-metrics/latest', name: 'activity_build_metrics_latest', methods: ['GET'])]
#[OA\Get(
    summary: 'Get the latest build metric for a project',
    tags: ['Activity / Build Metrics'],
    parameters: [new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'Latest build metric'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class GetLatestBuildMetricController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(string $projectId): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetLatestBuildMetricQuery($projectId));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}

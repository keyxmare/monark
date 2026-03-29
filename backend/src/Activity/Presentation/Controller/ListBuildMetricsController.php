<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Query\ListBuildMetricsQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/projects/{projectId}/build-metrics', name: 'activity_build_metrics_list', methods: ['GET'])]
#[OA\Get(
    summary: 'List build metrics for a project',
    tags: ['Activity / Build Metrics'],
    parameters: [
        new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
    ],
    responses: [new OA\Response(response: 200, description: 'Paginated list of build metrics')],
)]
final readonly class ListBuildMetricsController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(string $projectId, Request $request): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListBuildMetricsQuery(
            projectId: $projectId,
            page: $request->query->getInt('page', 1),
            perPage: $request->query->getInt('per_page', 20),
        ));
        /** @var \App\Activity\Application\DTO\BuildMetricListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->data->toArray())->toArray());
    }
}

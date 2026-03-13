<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Query\ListBuildMetricsQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/projects/{projectId}/build-metrics', name: 'activity_build_metrics_list', methods: ['GET'])]
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

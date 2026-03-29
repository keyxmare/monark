<?php

declare(strict_types=1);

namespace App\Dependency\Presentation\Controller;

use App\Dependency\Application\Query\GetDependencyStatsQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dependency/stats', name: 'dependency_stats', methods: ['GET'])]
#[OA\Get(
    summary: 'Get dependency statistics',
    tags: ['Dependency / Dependencies'],
    parameters: [
        new OA\Parameter(name: 'project_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'package_manager', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string')),
    ],
    responses: [new OA\Response(response: 200, description: 'Dependency statistics')],
)]
final readonly class GetDependencyStatsController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetDependencyStatsQuery(
            projectId: $request->query->get('project_id'),
            packageManager: $request->query->get('package_manager'),
            type: $request->query->get('type'),
        ));

        /** @var \App\Dependency\Application\DTO\DependencyStatsOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(
            ApiResponse::success($result)->toArray(),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Coverage\Presentation\Controller;

use App\Coverage\Application\Query\GetProjectCoverageHistoryQuery;
use App\Shared\Application\DTO\ApiResponse;
use App\Shared\Domain\Exception\NotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/coverage/{projectSlug}', name: 'coverage_project_history_get', methods: ['GET'])]
#[OA\Get(
    summary: 'Get coverage history for a project',
    tags: ['Coverage'],
    parameters: [new OA\Parameter(name: 'projectSlug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
    responses: [
        new OA\Response(response: 200, description: 'Project coverage history'),
        new OA\Response(response: 404, description: 'Project not found'),
    ],
)]
final readonly class GetProjectCoverageHistoryController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(string $projectSlug): JsonResponse
    {
        try {
            $envelope = $this->queryBus->dispatch(new GetProjectCoverageHistoryQuery($projectSlug));
            $result = $envelope->last(HandledStamp::class)?->getResult();
        } catch (NotFoundException $e) {
            return new JsonResponse(
                ApiResponse::error($e->getMessage(), 404)->toArray(),
                404,
            );
        }

        return new JsonResponse(ApiResponse::success([
            'project' => $result['project'],
            'snapshots' => $result['snapshots'],
        ])->toArray());
    }
}

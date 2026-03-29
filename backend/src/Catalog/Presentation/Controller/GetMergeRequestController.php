<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Query\GetMergeRequestQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/merge-requests/{id}', name: 'catalog_merge_requests_get', methods: ['GET'])]
#[OA\Get(
    summary: 'Get a merge request by ID',
    tags: ['Catalog / Merge Requests'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'Merge request details'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class GetMergeRequestController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetMergeRequestQuery($id));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}

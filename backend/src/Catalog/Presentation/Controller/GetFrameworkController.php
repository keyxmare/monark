<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Query\GetFrameworkQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/frameworks/{id}', name: 'catalog_frameworks_get', methods: ['GET'])]
#[OA\Get(
    summary: 'Get a framework',
    tags: ['Catalog / Frameworks'],
    responses: [
        new OA\Response(response: 200, description: 'Framework found'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class GetFrameworkController
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetFrameworkQuery($id));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}

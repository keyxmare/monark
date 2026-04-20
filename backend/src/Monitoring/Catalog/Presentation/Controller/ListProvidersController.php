<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Presentation\Controller;

use App\Hub\Shared\Application\DTO\ApiResponse;
use App\Monitoring\Catalog\Application\DTO\ProviderListOutput;
use App\Monitoring\Catalog\Application\Query\ListProvidersQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/catalog/providers', name: 'catalog_providers_list', methods: ['GET'])]
#[OA\Get(
    summary: 'List providers',
    tags: ['Catalog / Providers'],
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
    ],
    responses: [new OA\Response(response: 200, description: 'Paginated list of providers')],
)]
final readonly class ListProvidersController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);

        $envelope = $this->queryBus->dispatch(new ListProvidersQuery($page, $perPage));
        /** @var ProviderListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}

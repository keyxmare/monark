<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Query\ListLanguagesQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/languages', name: 'catalog_languages_list', methods: ['GET'])]
#[OA\Get(
    summary: 'List languages',
    tags: ['Catalog / Languages'],
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        new OA\Parameter(name: 'project_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [new OA\Response(response: 200, description: 'Languages list')],
)]
final readonly class ListLanguagesController
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListLanguagesQuery(
            page: $request->query->getInt('page', 1),
            perPage: $request->query->getInt('per_page', 20),
            projectId: $request->query->get('project_id'),
        ));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}

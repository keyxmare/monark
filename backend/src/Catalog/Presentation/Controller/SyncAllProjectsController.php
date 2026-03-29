<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\SyncAllProjectsCommand;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SyncAllProjectsController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    #[Route('/api/catalog/sync-all', name: 'catalog_sync_all', methods: ['POST'])]
    #[OA\Post(
        summary: 'Sync all projects',
        tags: ['Catalog / Sync'],
        parameters: [new OA\Parameter(name: 'force', in: 'query', schema: new OA\Schema(type: 'boolean', default: false))],
        responses: [new OA\Response(response: 202, description: 'Sync started')],
    )]
    public function syncAll(Request $request): JsonResponse
    {
        $force = $request->query->getBoolean('force', false);
        $envelope = $this->commandBus->dispatch(new SyncAllProjectsCommand(force: $force));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 202);
    }

    #[Route('/api/catalog/providers/{id}/sync-all', name: 'catalog_provider_sync_all', methods: ['POST'])]
    #[OA\Post(
        summary: 'Sync all projects for a specific provider',
        tags: ['Catalog / Sync'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'force', in: 'query', schema: new OA\Schema(type: 'boolean', default: false)),
        ],
        responses: [new OA\Response(response: 202, description: 'Sync started')],
    )]
    public function syncByProvider(string $id, Request $request): JsonResponse
    {
        $force = $request->query->getBoolean('force', false);
        $body = $request->toArray();
        /** @var list<string>|null $projectIds */
        $projectIds = isset($body['projectIds']) && \is_array($body['projectIds']) ? $body['projectIds'] : null;

        $envelope = $this->commandBus->dispatch(new SyncAllProjectsCommand($id, $force, $projectIds));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 202);
    }
}

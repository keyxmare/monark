<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\SyncAllProjectsCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function syncAll(): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new SyncAllProjectsCommand());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 202);
    }

    #[Route('/api/catalog/providers/{id}/sync-all', name: 'catalog_provider_sync_all', methods: ['POST'])]
    public function syncByProvider(string $id): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new SyncAllProjectsCommand($id));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 202);
    }
}

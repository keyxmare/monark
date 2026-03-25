<?php

declare(strict_types=1);

namespace App\Dependency\Presentation\Controller;

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/dependency/sync', name: 'dependency_sync', methods: ['POST'])]
final readonly class SyncDependencyVersionsController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $syncId = Uuid::v7()->toRfc4122();

        $this->commandBus->dispatch(new SyncDependencyVersionsCommand(
            syncId: $syncId,
        ));

        return new JsonResponse(
            ApiResponse::success(['syncId' => $syncId])->toArray(),
            202,
        );
    }
}

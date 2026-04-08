<?php

declare(strict_types=1);

namespace App\VersionRegistry\Presentation\Controller;

use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/version-registry')]
final readonly class SyncProductVersionsController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    #[Route('/sync', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        $syncId = Uuid::v7()->toRfc4122();
        $this->commandBus->dispatch(new SyncProductVersionsCommand(syncId: $syncId));

        return new JsonResponse(['syncId' => $syncId], Response::HTTP_ACCEPTED);
    }
}

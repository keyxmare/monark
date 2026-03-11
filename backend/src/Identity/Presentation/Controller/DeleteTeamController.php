<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\DeleteTeamCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/teams/{id}', name: 'identity_teams_delete', methods: ['DELETE'])]
final readonly class DeleteTeamController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteTeamCommand($id));

        return new JsonResponse(ApiResponse::success()->toArray(), 204);
    }
}

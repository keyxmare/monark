<?php

declare(strict_types=1);

namespace App\Dependency\Presentation\Controller;

use App\Dependency\Application\Command\DeleteDependencyCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dependency/dependencies/{id}', name: 'dependency_dependencies_delete', methods: ['DELETE'])]
final readonly class DeleteDependencyController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteDependencyCommand($id));

        return new JsonResponse(ApiResponse::success()->toArray(), 204);
    }
}

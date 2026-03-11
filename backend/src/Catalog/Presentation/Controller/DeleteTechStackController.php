<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\DeleteTechStackCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/tech-stacks/{id}', name: 'catalog_tech_stacks_delete', methods: ['DELETE'])]
final readonly class DeleteTechStackController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteTechStackCommand($id));

        return new JsonResponse(ApiResponse::success()->toArray(), 204);
    }
}

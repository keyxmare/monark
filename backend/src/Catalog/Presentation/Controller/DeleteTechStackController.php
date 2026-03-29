<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\DeleteTechStackCommand;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/tech-stacks/{id}', name: 'catalog_tech_stacks_delete', methods: ['DELETE'])]
#[OA\Delete(
    summary: 'Delete a tech stack',
    tags: ['Catalog / Tech Stacks'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [new OA\Response(response: 204, description: 'Deleted')],
)]
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

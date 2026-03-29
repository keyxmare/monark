<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\DeleteProjectCommand;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/projects/{id}', name: 'catalog_projects_delete', methods: ['DELETE'])]
#[OA\Delete(
    summary: 'Delete a project',
    tags: ['Catalog / Projects'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [new OA\Response(response: 204, description: 'Deleted')],
)]
final readonly class DeleteProjectController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteProjectCommand($id));

        return new JsonResponse(ApiResponse::success()->toArray(), 204);
    }
}

<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\DeleteProviderCommand;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/providers/{id}', name: 'catalog_providers_delete', methods: ['DELETE'])]
#[OA\Delete(
    summary: 'Delete a provider',
    tags: ['Catalog / Providers'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [new OA\Response(response: 204, description: 'Deleted')],
)]
final readonly class DeleteProviderController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteProviderCommand($id));

        return new JsonResponse(ApiResponse::success()->toArray(), 204);
    }
}

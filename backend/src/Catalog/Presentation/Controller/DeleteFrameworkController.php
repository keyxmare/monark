<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\DeleteFrameworkCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/frameworks/{id}', name: 'catalog_frameworks_delete', methods: ['DELETE'])]
#[OA\Delete(
    summary: 'Delete a framework',
    tags: ['Catalog / Frameworks'],
    responses: [
        new OA\Response(response: 204, description: 'Framework deleted'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class DeleteFrameworkController
{
    public function __construct(private MessageBusInterface $commandBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteFrameworkCommand($id));

        return new JsonResponse(null, 204);
    }
}

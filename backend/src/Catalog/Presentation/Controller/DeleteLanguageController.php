<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\DeleteLanguageCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/languages/{id}', name: 'catalog_languages_delete', methods: ['DELETE'])]
#[OA\Delete(
    summary: 'Delete a language',
    tags: ['Catalog / Languages'],
    responses: [
        new OA\Response(response: 204, description: 'Language deleted'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class DeleteLanguageController
{
    public function __construct(private MessageBusInterface $commandBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteLanguageCommand($id));

        return new JsonResponse(null, 204);
    }
}

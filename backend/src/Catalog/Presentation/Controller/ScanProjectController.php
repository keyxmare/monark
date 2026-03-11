<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/projects/{id}/scan', name: 'catalog_projects_scan', methods: ['POST'])]
final readonly class ScanProjectController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new ScanProjectCommand($id));

        return new JsonResponse(
            ApiResponse::success(['message' => 'Scan started'])->toArray(),
            202,
        );
    }
}

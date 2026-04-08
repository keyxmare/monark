<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\CommandHandler\ScanProjectHandler;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/projects/{id}/scan', name: 'catalog_projects_scan', methods: ['POST'])]
#[OA\Post(
    summary: 'Trigger a scan for a project',
    tags: ['Catalog / Projects'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'Scan result'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class ScanProjectController
{
    public function __construct(
        private ScanProjectHandler $scanProjectHandler,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $result = ($this->scanProjectHandler)(new ScanProjectCommand($id));

        return new JsonResponse(
            ApiResponse::success($result)->toArray(),
        );
    }
}

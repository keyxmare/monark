<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Presentation\Controller;

use App\Hub\Shared\Application\DTO\ApiResponse;
use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/catalog/projects/{id}/branches', name: 'catalog_project_branches', methods: ['GET'])]
#[OA\Get(
    summary: 'List available branches for a project',
    tags: ['Catalog / Projects'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'List of branch names'),
        new OA\Response(response: 404, description: 'Project not found'),
    ],
)]
final readonly class ListProjectBranchesController
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private GitProviderFactoryInterface $gitProviderFactory,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $project = $this->projectRepository->findById(Uuid::fromString($id));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $id);
        }

        $provider = $project->getProvider();
        if ($provider === null || $project->getExternalId() === null) {
            return new JsonResponse(ApiResponse::success([])->toArray());
        }

        $client = $this->gitProviderFactory->create($provider);
        $branches = $client->listBranches($provider, $project->getExternalId());

        return new JsonResponse(ApiResponse::success($branches)->toArray());
    }
}

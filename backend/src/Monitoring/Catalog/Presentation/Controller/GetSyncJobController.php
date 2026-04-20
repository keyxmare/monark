<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Presentation\Controller;

use App\Hub\Shared\Application\DTO\ApiResponse;
use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use DateTimeInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class GetSyncJobController
{
    public function __construct(
        private SyncJobRepositoryInterface $syncJobRepository,
    ) {
    }

    #[Route('/catalog/sync-jobs/{id}', name: 'catalog_sync_job_get', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get a sync job by ID',
        tags: ['Catalog / Sync'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 200, description: 'Sync job details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function __invoke(string $id): JsonResponse
    {
        $syncJob = $this->syncJobRepository->findById(Uuid::fromString($id));
        if ($syncJob === null) {
            throw NotFoundException::forEntity('SyncJob', $id);
        }

        return new JsonResponse(ApiResponse::success([
            'id' => $syncJob->getId()->toRfc4122(),
            'totalProjects' => $syncJob->getTotalProjects(),
            'completedProjects' => $syncJob->getCompletedProjects(),
            'status' => $syncJob->getStatus()->value,
            'createdAt' => $syncJob->getCreatedAt()->format(DateTimeInterface::ATOM),
            'completedAt' => $syncJob->getCompletedAt()?->format(DateTimeInterface::ATOM),
        ])->toArray());
    }
}

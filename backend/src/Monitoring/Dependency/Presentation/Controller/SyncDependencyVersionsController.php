<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Presentation\Controller;

use App\Hub\Shared\Application\DTO\ApiResponse;
use App\Monitoring\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/dependency/sync', name: 'dependency_sync', methods: ['POST'])]
#[OA\Post(
    summary: 'Sync dependency versions from registries',
    tags: ['Dependency / Dependencies'],
    responses: [new OA\Response(response: 202, description: 'Sync started')],
)]
final readonly class SyncDependencyVersionsController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $syncId = Uuid::v7()->toRfc4122();
        $total = \count($this->dependencyRepository->findUniquePackages());

        $this->commandBus->dispatch(new SyncDependencyVersionsCommand(
            syncId: $syncId,
        ));

        return new JsonResponse(
            ApiResponse::success(['syncId' => $syncId, 'total' => $total])->toArray(),
            202,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Sync\Presentation\Controller;

use App\Shared\Application\DTO\ApiResponse;
use App\Sync\Application\Command\GlobalSyncCommand;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Routing\Attribute\Route;

final readonly class StartGlobalSyncController
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $globalSyncJobRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    #[Route('/api/v1/sync', name: 'global_sync_start', methods: ['POST'])]
    #[OA\Post(
        summary: 'Start global sync workflow',
        tags: ['Sync'],
        responses: [
            new OA\Response(response: 202, description: 'Sync started'),
            new OA\Response(response: 409, description: 'Sync already running'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $running = $this->globalSyncJobRepository->findRunning();
        if ($running !== null) {
            return new JsonResponse(
                ApiResponse::error('A sync is already running', 409)->toArray(),
                409,
            );
        }

        $job = GlobalSyncJob::create();
        $this->globalSyncJobRepository->save($job);
        $syncId = $job->getId()->toRfc4122();

        $this->commandBus->dispatch(
            new GlobalSyncCommand($syncId),
            [new DispatchAfterCurrentBusStamp()],
        );

        return new JsonResponse(
            ApiResponse::success([
                'syncId' => $syncId,
                'status' => $job->getStatus()->value,
                'currentStep' => $job->getCurrentStep(),
            ])->toArray(),
            202,
        );
    }
}

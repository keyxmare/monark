<?php

declare(strict_types=1);

namespace App\Monitoring\Sync\Presentation\Controller;

use App\Hub\Shared\Application\DTO\ApiResponse;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final readonly class CancelGlobalSyncController
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $globalSyncJobRepository,
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/sync/cancel', name: 'global_sync_cancel', methods: ['POST'])]
    #[OA\Post(
        summary: 'Cancel the currently running global sync',
        tags: ['Sync'],
        responses: [
            new OA\Response(response: 200, description: 'Sync marked as failed'),
            new OA\Response(response: 404, description: 'No running sync to cancel'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $running = $this->globalSyncJobRepository->findRunning();
        if ($running === null) {
            return new JsonResponse(
                ApiResponse::error('No sync is currently running', 404)->toArray(),
                404,
            );
        }

        $running->markFailed();
        $this->globalSyncJobRepository->save($running);

        $syncId = $running->getId()->toRfc4122();

        try {
            $this->mercureHub->publish(new Update(
                \sprintf('/global-sync/%s', $syncId),
                (string) \json_encode([
                    'syncId' => $syncId,
                    'status' => $running->getStatus()->value,
                    'currentStep' => $running->getCurrentStep(),
                    'currentStepName' => $running->getCurrentStepName(),
                    'stepProgress' => $running->getStepProgress(),
                    'stepTotal' => $running->getStepTotal(),
                    'completedSteps' => $running->getCompletedStepNames(),
                    'message' => 'cancelled',
                ]),
            ));
        } catch (Throwable $e) {
            $this->logger->warning('Failed to publish sync cancel to Mercure', [
                'syncId' => $syncId,
                'error' => $e->getMessage(),
            ]);
        }

        return new JsonResponse(
            ApiResponse::success([
                'syncId' => $syncId,
                'status' => $running->getStatus()->value,
            ])->toArray(),
        );
    }
}

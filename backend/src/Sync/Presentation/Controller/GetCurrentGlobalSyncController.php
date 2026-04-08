<?php

declare(strict_types=1);

namespace App\Sync\Presentation\Controller;

use App\Shared\Application\DTO\ApiResponse;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use DateTimeInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetCurrentGlobalSyncController
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $globalSyncJobRepository,
    ) {
    }

    #[Route('/api/v1/sync/current', name: 'global_sync_current', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get current running global sync',
        tags: ['Sync'],
        responses: [
            new OA\Response(response: 200, description: 'Current sync or null'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $job = $this->globalSyncJobRepository->findRunning();

        if ($job === null) {
            return new JsonResponse(ApiResponse::success(null)->toArray());
        }

        return new JsonResponse(ApiResponse::success([
            'syncId' => $job->getId()->toRfc4122(),
            'status' => $job->getStatus()->value,
            'currentStep' => $job->getCurrentStep(),
            'currentStepName' => $job->getCurrentStepName(),
            'stepProgress' => $job->getStepProgress(),
            'stepTotal' => $job->getStepTotal(),
            'completedSteps' => $job->getCompletedStepNames(),
            'createdAt' => $job->getCreatedAt()->format(DateTimeInterface::ATOM),
        ])->toArray());
    }
}

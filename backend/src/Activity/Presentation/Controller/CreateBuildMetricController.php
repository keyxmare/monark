<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Command\CreateBuildMetricCommand;
use App\Activity\Application\DTO\CreateBuildMetricInput;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/projects/{projectId}/build-metrics', name: 'activity_build_metrics_create', methods: ['POST'])]
final readonly class CreateBuildMetricController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $projectId, CreateBuildMetricInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateBuildMetricCommand($projectId, $input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), Response::HTTP_CREATED);
    }
}

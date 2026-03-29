<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Command\CreateBuildMetricCommand;
use App\Activity\Application\DTO\CreateBuildMetricInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/activity/projects/{projectId}/build-metrics', name: 'activity_build_metrics_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a build metric',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateBuildMetricInput::class)),
    ),
    tags: ['Activity / Build Metrics'],
    parameters: [new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 201, description: 'Build metric created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateBuildMetricController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $projectId, #[MapRequestPayload] CreateBuildMetricInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateBuildMetricCommand($projectId, $input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), Response::HTTP_CREATED);
    }
}

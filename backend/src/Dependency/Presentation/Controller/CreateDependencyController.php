<?php

declare(strict_types=1);

namespace App\Dependency\Presentation\Controller;

use App\Dependency\Application\Command\CreateDependencyCommand;
use App\Dependency\Application\DTO\CreateDependencyInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/dependency/dependencies', name: 'dependency_dependencies_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a dependency',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateDependencyInput::class)),
    ),
    tags: ['Dependency / Dependencies'],
    responses: [
        new OA\Response(response: 201, description: 'Dependency created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateDependencyController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateDependencyInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateDependencyCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

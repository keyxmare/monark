<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Presentation\Controller;

use App\Hub\Shared\Application\DTO\ApiResponse;
use App\Monitoring\Catalog\Application\Command\CreateProjectCommand;
use App\Monitoring\Catalog\Application\DTO\CreateProjectInput;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/catalog/projects', name: 'catalog_projects_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a project',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateProjectInput::class)),
    ),
    tags: ['Catalog / Projects'],
    responses: [
        new OA\Response(response: 201, description: 'Project created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateProjectController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateProjectInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateProjectCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

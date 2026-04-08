<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\UpdateProjectCommand;
use App\Catalog\Application\DTO\UpdateProjectInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/projects/{id}', name: 'catalog_projects_update', methods: ['PUT'])]
#[OA\Put(
    summary: 'Update a project',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: UpdateProjectInput::class)),
    ),
    tags: ['Catalog / Projects'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'Project updated'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class UpdateProjectController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] UpdateProjectInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new UpdateProjectCommand($id, $input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}

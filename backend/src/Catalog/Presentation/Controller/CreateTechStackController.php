<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\CreateTechStackCommand;
use App\Catalog\Application\DTO\CreateTechStackInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/tech-stacks', name: 'catalog_tech_stacks_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a tech stack',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateTechStackInput::class)),
    ),
    tags: ['Catalog / Tech Stacks'],
    responses: [
        new OA\Response(response: 201, description: 'Tech stack created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateTechStackController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateTechStackInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateTechStackCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

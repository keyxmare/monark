<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\CreateFrameworkCommand;
use App\Catalog\Application\DTO\CreateFrameworkInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/frameworks', name: 'catalog_frameworks_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a framework',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateFrameworkInput::class)),
    ),
    tags: ['Catalog / Frameworks'],
    responses: [
        new OA\Response(response: 201, description: 'Framework created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateFrameworkController
{
    public function __construct(private MessageBusInterface $commandBus)
    {
    }

    public function __invoke(#[MapRequestPayload] CreateFrameworkInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateFrameworkCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

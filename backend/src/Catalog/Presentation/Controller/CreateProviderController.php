<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\CreateProviderCommand;
use App\Catalog\Application\DTO\CreateProviderInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/providers', name: 'catalog_providers_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a provider',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateProviderInput::class)),
    ),
    tags: ['Catalog / Providers'],
    responses: [
        new OA\Response(response: 201, description: 'Provider created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateProviderController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateProviderInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateProviderCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

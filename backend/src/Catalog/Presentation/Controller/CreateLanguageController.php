<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\CreateLanguageCommand;
use App\Catalog\Application\DTO\CreateLanguageInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/languages', name: 'catalog_languages_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a language',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateLanguageInput::class)),
    ),
    tags: ['Catalog / Languages'],
    responses: [
        new OA\Response(response: 201, description: 'Language created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateLanguageController
{
    public function __construct(private MessageBusInterface $commandBus)
    {
    }

    public function __invoke(#[MapRequestPayload] CreateLanguageInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateLanguageCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

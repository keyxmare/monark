<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\ImportProjectsCommand;
use App\Catalog\Application\DTO\ImportProjectsInput;
use App\Identity\Domain\Model\User;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/providers/{id}/import', name: 'catalog_providers_import', methods: ['POST'])]
#[OA\Post(
    summary: 'Import projects from a provider',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: ImportProjectsInput::class)),
    ),
    tags: ['Catalog / Providers'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 201, description: 'Projects imported'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class ImportProjectsController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] ImportProjectsInput $input): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $envelope = $this->commandBus->dispatch(
            new ImportProjectsCommand($id, $input, $user->getId()->toRfc4122()),
        );
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

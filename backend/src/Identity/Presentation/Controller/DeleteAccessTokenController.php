<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\DeleteAccessTokenCommand;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/access-tokens/{id}', name: 'identity_access_tokens_delete', methods: ['DELETE'])]
#[OA\Delete(
    summary: 'Delete an access token',
    tags: ['Identity / Access Tokens'],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [new OA\Response(response: 204, description: 'Deleted')],
)]
final readonly class DeleteAccessTokenController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteAccessTokenCommand($id));

        return new JsonResponse(ApiResponse::success()->toArray(), 204);
    }
}

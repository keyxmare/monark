<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\CreateAccessTokenCommand;
use App\Identity\Application\DTO\CreateAccessTokenInput;
use App\Identity\Domain\Model\User;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/identity/access-tokens', name: 'identity_access_tokens_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create an access token',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateAccessTokenInput::class)),
    ),
    tags: ['Identity / Access Tokens'],
    responses: [
        new OA\Response(response: 201, description: 'Token created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateAccessTokenController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[CurrentUser] User $user,
        #[MapRequestPayload] CreateAccessTokenInput $input,
    ): JsonResponse {
        $envelope = $this->commandBus->dispatch(
            new CreateAccessTokenCommand($user->getId()->toRfc4122(), $input),
        );
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

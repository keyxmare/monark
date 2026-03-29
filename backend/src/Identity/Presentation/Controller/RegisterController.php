<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\RegisterUserCommand;
use App\Identity\Application\DTO\RegisterUserInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/register', name: 'identity_auth_register', methods: ['POST'])]
#[OA\Post(
    summary: 'Register a new user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: RegisterUserInput::class)),
    ),
    tags: ['Identity / Auth'],
    responses: [
        new OA\Response(response: 201, description: 'User registered'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class RegisterController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(#[MapRequestPayload] RegisterUserInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new RegisterUserCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}

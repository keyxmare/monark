<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Mapper\UserMapper;
use App\Identity\Domain\Model\User;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private ApiTokenHandler $apiTokenHandler,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        /** @var User $user */
        $user = $token->getUser();

        $apiToken = $this->apiTokenHandler->createToken($user->getId()->toRfc4122());

        return new JsonResponse(ApiResponse::success([
            'token' => $apiToken,
            'user' => UserMapper::toOutput($user),
        ])->toArray());
    }
}

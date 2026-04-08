<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(
            ApiResponse::error($this->translator->trans('error.invalid_credentials'), 401)->toArray(),
            401,
        );
    }
}

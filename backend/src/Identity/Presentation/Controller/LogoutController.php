<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/logout', name: 'identity_auth_logout', methods: ['POST'])]
final readonly class LogoutController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(ApiResponse::success(['message' => 'Logged out successfully.'])->toArray());
    }
}

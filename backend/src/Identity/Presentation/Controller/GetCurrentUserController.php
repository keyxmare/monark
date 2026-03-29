<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\DTO\UserOutput;
use App\Identity\Domain\Model\User;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/auth/profile', name: 'identity_auth_profile', methods: ['GET'])]
#[OA\Get(
    summary: 'Get the current authenticated user profile',
    tags: ['Identity / Auth'],
    responses: [
        new OA\Response(response: 200, description: 'Current user profile'),
        new OA\Response(response: 401, description: 'Not authenticated'),
    ],
)]
final readonly class GetCurrentUserController
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse(ApiResponse::success(UserOutput::fromEntity($user))->toArray());
    }
}

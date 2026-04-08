<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth/login', name: 'identity_auth_login', methods: ['POST'])]
#[OA\Post(
    summary: 'Authenticate and obtain a token',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string'),
            ],
        ),
    ),
    tags: ['Identity / Auth'],
    responses: [
        new OA\Response(response: 200, description: 'Authentication successful'),
        new OA\Response(response: 401, description: 'Invalid credentials'),
    ],
)]
final readonly class LoginController
{
    public function __invoke(): never
    {
        throw new LogicException('This endpoint is handled by the json_login authenticator.');
    }
}

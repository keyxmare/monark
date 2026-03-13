<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use LogicException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/login', name: 'identity_auth_login', methods: ['POST'])]
final readonly class LoginController
{
    public function __invoke(): never
    {
        throw new LogicException('This endpoint is handled by the json_login authenticator.');
    }
}

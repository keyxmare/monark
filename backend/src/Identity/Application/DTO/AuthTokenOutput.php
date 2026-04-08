<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

final readonly class AuthTokenOutput
{
    public function __construct(
        public string $token,
        public UserOutput $user,
    ) {
    }
}

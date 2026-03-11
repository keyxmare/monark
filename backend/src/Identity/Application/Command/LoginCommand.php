<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\DTO\LoginInput;

final readonly class LoginCommand
{
    public function __construct(
        public LoginInput $input,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\DTO\RegisterUserInput;

final readonly class RegisterUserCommand
{
    public function __construct(
        public RegisterUserInput $input,
    ) {
    }
}

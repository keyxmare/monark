<?php

declare(strict_types=1);

namespace App\Hub\Identity\Application\Command;

use App\Hub\Identity\Application\DTO\RegisterUserInput;

final readonly class RegisterUserCommand
{
    public function __construct(
        public RegisterUserInput $input,
    ) {
    }
}

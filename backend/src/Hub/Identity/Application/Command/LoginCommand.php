<?php

declare(strict_types=1);

namespace App\Hub\Identity\Application\Command;

use App\Hub\Identity\Application\DTO\LoginInput;

final readonly class LoginCommand
{
    public function __construct(
        public LoginInput $input,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\DTO\UpdateUserInput;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $userId,
        public UpdateUserInput $input,
    ) {
    }
}

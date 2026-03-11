<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\DTO\CreateAccessTokenInput;

final readonly class CreateAccessTokenCommand
{
    public function __construct(
        public string $userId,
        public CreateAccessTokenInput $input,
    ) {
    }
}

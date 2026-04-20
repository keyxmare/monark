<?php

declare(strict_types=1);

namespace App\Hub\Identity\Domain\Event;

final readonly class UserCreated
{
    public function __construct(
        public string $userId,
        public string $email,
    ) {
    }
}

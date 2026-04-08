<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

final readonly class UserUpdated
{
    public function __construct(
        public string $userId,
    ) {
    }
}

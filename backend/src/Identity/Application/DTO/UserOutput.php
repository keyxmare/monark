<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

final readonly class UserOutput
{
    /** @param list<string> $roles */
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $avatar,
        public array $roles,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}

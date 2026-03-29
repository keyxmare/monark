<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

final readonly class AccessTokenOutput
{
    /** @param list<string> $scopes */
    public function __construct(
        public string $id,
        public string $provider,
        public array $scopes,
        public ?string $expiresAt,
        public string $userId,
        public string $createdAt,
    ) {
    }
}

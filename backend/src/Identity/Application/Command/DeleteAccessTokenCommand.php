<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

final readonly class DeleteAccessTokenCommand
{
    public function __construct(
        public string $tokenId,
    ) {
    }
}

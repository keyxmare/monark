<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use App\Identity\Domain\Model\AccessToken;

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

    public static function fromEntity(AccessToken $token): self
    {
        return new self(
            id: $token->getId()->toRfc4122(),
            provider: $token->getProvider()->value,
            scopes: $token->getScopes(),
            expiresAt: $token->getExpiresAt()?->format(\DateTimeInterface::ATOM),
            userId: $token->getUser()->getId()->toRfc4122(),
            createdAt: $token->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}

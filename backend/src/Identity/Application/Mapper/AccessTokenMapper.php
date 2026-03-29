<?php

declare(strict_types=1);

namespace App\Identity\Application\Mapper;

use App\Identity\Application\DTO\AccessTokenOutput;
use App\Identity\Domain\Model\AccessToken;
use DateTimeInterface;

final class AccessTokenMapper
{
    public static function toOutput(AccessToken $token): AccessTokenOutput
    {
        return new AccessTokenOutput(
            id: $token->getId()->toRfc4122(),
            provider: $token->getProvider()->value,
            scopes: $token->getScopes(),
            expiresAt: $token->getExpiresAt()?->format(DateTimeInterface::ATOM),
            userId: $token->getUser()->getId()->toRfc4122(),
            createdAt: $token->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}

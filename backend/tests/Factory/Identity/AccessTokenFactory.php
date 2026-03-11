<?php

declare(strict_types=1);

namespace App\Tests\Factory\Identity;

use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\TokenProvider;
use App\Identity\Domain\Model\User;

final class AccessTokenFactory
{
    public static function create(User $user, array $overrides = []): AccessToken
    {
        return AccessToken::create(
            provider: $overrides['provider'] ?? TokenProvider::Gitlab,
            token: $overrides['token'] ?? 'glpat-test-token-123',
            scopes: $overrides['scopes'] ?? ['read_api'],
            expiresAt: $overrides['expiresAt'] ?? null,
            user: $user,
        );
    }
}

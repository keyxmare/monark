<?php

declare(strict_types=1);

namespace App\Tests\Factory\Identity;

use App\Identity\Domain\Model\User;

final class UserFactory
{
    public static function create(array $overrides = []): User
    {
        return User::create(
            email: $overrides['email'] ?? 'john@example.com',
            hashedPassword: $overrides['hashedPassword'] ?? 'hashed_password',
            firstName: $overrides['firstName'] ?? 'John',
            lastName: $overrides['lastName'] ?? 'Doe',
            avatar: $overrides['avatar'] ?? null,
            roles: $overrides['roles'] ?? ['ROLE_USER'],
        );
    }
}

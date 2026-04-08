<?php

declare(strict_types=1);

namespace App\Tests\Helpers;

use App\Identity\Domain\Model\User;
use App\Identity\Infrastructure\Security\ApiTokenHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait AuthHelper
{
    protected function createAuthenticatedUser(array $overrides = []): array
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $tokenHandler = $container->get(ApiTokenHandler::class);

        $user = User::create(
            email: $overrides['email'] ?? 'test@example.com',
            hashedPassword: 'temp',
            firstName: $overrides['firstName'] ?? 'Test',
            lastName: $overrides['lastName'] ?? 'User',
            roles: $overrides['roles'] ?? ['ROLE_USER'],
        );

        $hashedPassword = $hasher->hashPassword($user, $overrides['password'] ?? 'password123');
        $user->updatePassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        $token = $tokenHandler->createToken($user->getId()->toRfc4122());

        return ['user' => $user, 'token' => $token];
    }

    protected function authHeader(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
    }
}

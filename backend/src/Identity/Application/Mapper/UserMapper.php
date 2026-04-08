<?php

declare(strict_types=1);

namespace App\Identity\Application\Mapper;

use App\Identity\Application\DTO\UserOutput;
use App\Identity\Domain\Model\User;
use DateTimeInterface;

final class UserMapper
{
    public static function toOutput(User $user): UserOutput
    {
        return new UserOutput(
            id: $user->getId()->toRfc4122(),
            email: $user->getEmail(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            avatar: $user->getAvatar(),
            roles: $user->getRoles(),
            createdAt: $user->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $user->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Hub\Identity\Application\QueryHandler;

use App\Hub\Identity\Application\DTO\UserOutput;
use App\Hub\Identity\Application\Mapper\UserMapper;
use App\Hub\Identity\Application\Query\GetUserQuery;
use App\Hub\Identity\Domain\Repository\UserRepositoryInterface;
use App\Hub\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(GetUserQuery $query): UserOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($query->userId));
        if ($user === null) {
            throw NotFoundException::forEntity('User', $query->userId);
        }

        return UserMapper::toOutput($user);
    }
}

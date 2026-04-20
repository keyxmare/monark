<?php

declare(strict_types=1);

namespace App\Hub\Identity\Application\QueryHandler;

use App\Hub\Identity\Application\DTO\UserListOutput;
use App\Hub\Identity\Application\Mapper\UserMapper;
use App\Hub\Identity\Application\Query\ListUsersQuery;
use App\Hub\Identity\Domain\Repository\UserRepositoryInterface;
use App\Hub\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListUsersHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ListUsersQuery $query): UserListOutput
    {
        $users = $this->userRepository->findAll($query->page, $query->perPage);
        $total = $this->userRepository->count();

        $items = \array_map(
            static fn ($user) => UserMapper::toOutput($user),
            $users,
        );

        return new UserListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}

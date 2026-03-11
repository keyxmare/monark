<?php

declare(strict_types=1);

namespace App\Identity\Application\QueryHandler;

use App\Identity\Application\DTO\UserListOutput;
use App\Identity\Application\DTO\UserOutput;
use App\Identity\Application\Query\ListUsersQuery;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
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
            static fn ($user) => UserOutput::fromEntity($user),
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

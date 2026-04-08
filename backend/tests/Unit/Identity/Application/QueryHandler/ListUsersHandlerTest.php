<?php

declare(strict_types=1);

use App\Identity\Application\DTO\UserListOutput;
use App\Identity\Application\Query\ListUsersQuery;
use App\Identity\Application\QueryHandler\ListUsersHandler;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListUsersRepo(array $users = [], int $count = 0): UserRepositoryInterface
{
    return new class ($users, $count) implements UserRepositoryInterface {
        public function __construct(private readonly array $users, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?User
        {
            return null;
        }
        public function findByEmail(string $email): ?User
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->users;
        }
        public function count(): int
        {
            return $this->count;
        }
        public function save(User $user): void
        {
        }
    };
}

describe('ListUsersHandler', function () {
    it('returns paginated users', function () {
        $user1 = User::create(email: 'john@example.com', hashedPassword: 'h', firstName: 'John', lastName: 'Doe');
        $user2 = User::create(email: 'jane@example.com', hashedPassword: 'h', firstName: 'Jane', lastName: 'Smith');

        $handler = new ListUsersHandler(\stubListUsersRepo([$user1, $user2], 2));
        $result = $handler(new ListUsersQuery(1, 20));

        expect($result)->toBeInstanceOf(UserListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
        expect($result->pagination->page)->toBe(1);
    });

    it('returns empty list when no users', function () {
        $handler = new ListUsersHandler(\stubListUsersRepo([], 0));
        $result = $handler(new ListUsersQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});

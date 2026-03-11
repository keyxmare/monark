<?php

declare(strict_types=1);

use App\Identity\Application\DTO\UserOutput;
use App\Identity\Application\Query\GetUserQuery;
use App\Identity\Application\QueryHandler\GetUserHandler;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetUserRepo(?User $user = null): UserRepositoryInterface
{
    return new class ($user) implements UserRepositoryInterface {
        public function __construct(private readonly ?User $user) {}
        public function findById(Uuid $id): ?User { return $this->user; }
        public function findByEmail(string $email): ?User { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(User $user): void {}
    };
}

describe('GetUserHandler', function () {
    it('returns a user by id', function () {
        $user = User::create(
            email: 'john@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        $handler = new GetUserHandler(stubGetUserRepo($user));
        $result = $handler(new GetUserQuery($user->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(UserOutput::class);
        expect($result->email)->toBe('john@example.com');
        expect($result->firstName)->toBe('John');
    });

    it('throws not found when user does not exist', function () {
        $handler = new GetUserHandler(stubGetUserRepo(null));
        $handler(new GetUserQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});

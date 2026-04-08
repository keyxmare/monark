<?php

declare(strict_types=1);

use App\Identity\Application\Command\LoginCommand;
use App\Identity\Application\CommandHandler\LoginHandler;
use App\Identity\Application\DTO\AuthTokenOutput;
use App\Identity\Application\DTO\LoginInput;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;

function stubLoginUserRepo(?User $user = null): UserRepositoryInterface
{
    return new class ($user) implements UserRepositoryInterface {
        public function __construct(private readonly ?User $user)
        {
        }
        public function findById(Uuid $id): ?User
        {
            return null;
        }
        public function findByEmail(string $email): ?User
        {
            return $this->user;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(User $user): void
        {
        }
    };
}

function stubLoginHasher(bool $valid): UserPasswordHasherInterface
{
    return new class ($valid) implements UserPasswordHasherInterface {
        public function __construct(private readonly bool $valid)
        {
        }
        public function hashPassword(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): string
        {
            return '';
        }
        public function isPasswordValid(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): bool
        {
            return $this->valid;
        }
        public function needsRehash(PasswordAuthenticatedUserInterface $user): bool
        {
            return false;
        }
    };
}

describe('LoginHandler', function () {
    it('logs in a user with valid credentials', function () {
        $user = User::create(
            email: 'john@example.com',
            hashedPassword: 'hashed_password',
            firstName: 'John',
            lastName: 'Doe',
        );

        $handler = new LoginHandler(\stubLoginUserRepo($user), \stubLoginHasher(true));

        $input = new LoginInput(email: 'john@example.com', password: 'password123');
        $result = $handler(new LoginCommand($input));

        expect($result)->toBeInstanceOf(AuthTokenOutput::class);
        expect($result->token)->toBeString();
        expect($result->token)->toHaveLength(64);
        expect($result->user->email)->toBe('john@example.com');
    });

    it('throws exception when user not found', function () {
        $handler = new LoginHandler(\stubLoginUserRepo(null), \stubLoginHasher(true));

        $input = new LoginInput(email: 'unknown@example.com', password: 'password123');
        $handler(new LoginCommand($input));
    })->throws(\DomainException::class, 'Invalid credentials.');

    it('throws exception when password is invalid', function () {
        $user = User::create(
            email: 'john@example.com',
            hashedPassword: 'hashed_password',
            firstName: 'John',
            lastName: 'Doe',
        );

        $handler = new LoginHandler(\stubLoginUserRepo($user), \stubLoginHasher(false));

        $input = new LoginInput(email: 'john@example.com', password: 'wrong');
        $handler(new LoginCommand($input));
    })->throws(\DomainException::class, 'Invalid credentials.');
});

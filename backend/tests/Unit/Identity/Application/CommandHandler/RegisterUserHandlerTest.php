<?php

declare(strict_types=1);

use App\Identity\Application\Command\RegisterUserCommand;
use App\Identity\Application\CommandHandler\RegisterUserHandler;
use App\Identity\Application\DTO\RegisterUserInput;
use App\Identity\Application\DTO\UserOutput;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;

function stubUserRepo(?User $findByEmailResult = null): UserRepositoryInterface
{
    return new class ($findByEmailResult) implements UserRepositoryInterface {
        public ?User $saved = null;

        public function __construct(private readonly ?User $findByEmailResult) {}

        public function findById(Uuid $id): ?User { return null; }
        public function findByEmail(string $email): ?User { return $this->findByEmailResult; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(User $user): void { $this->saved = $user; }
    };
}

function stubPasswordHasher(string $hash = 'hashed_pw', bool $valid = true): UserPasswordHasherInterface
{
    return new class ($hash, $valid) implements UserPasswordHasherInterface {
        public function __construct(private readonly string $hash, private readonly bool $valid) {}

        public function hashPassword(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): string
        {
            return $this->hash;
        }

        public function isPasswordValid(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): bool
        {
            return $this->valid;
        }

        public function needsRehash(PasswordAuthenticatedUserInterface $user): bool { return false; }
    };
}

function stubEventBus(): MessageBusInterface
{
    return new class implements MessageBusInterface {
        public array $dispatched = [];

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched[] = $message;
            return new Envelope($message);
        }
    };
}

describe('RegisterUserHandler', function () {
    it('registers a new user successfully', function () {
        $repo = stubUserRepo(null);
        $hasher = stubPasswordHasher('hashed_pw');
        $bus = stubEventBus();
        $handler = new RegisterUserHandler($repo, $hasher, $bus);

        $input = new RegisterUserInput(
            email: 'john@example.com',
            password: 'password123',
            firstName: 'John',
            lastName: 'Doe',
        );

        $result = $handler(new RegisterUserCommand($input));

        expect($result)->toBeInstanceOf(UserOutput::class);
        expect($result->email)->toBe('john@example.com');
        expect($result->firstName)->toBe('John');
        expect($result->lastName)->toBe('Doe');
        expect($repo->saved)->not->toBeNull();
    });

    it('throws exception when email already exists', function () {
        $existingUser = User::create(
            email: 'john@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        $repo = stubUserRepo($existingUser);
        $hasher = stubPasswordHasher();
        $bus = stubEventBus();
        $handler = new RegisterUserHandler($repo, $hasher, $bus);

        $input = new RegisterUserInput(
            email: 'john@example.com',
            password: 'password123',
            firstName: 'John',
            lastName: 'Doe',
        );

        $handler(new RegisterUserCommand($input));
    })->throws(\DomainException::class, 'A user with this email already exists.');
});

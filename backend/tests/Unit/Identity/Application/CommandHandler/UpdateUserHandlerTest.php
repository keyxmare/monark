<?php

declare(strict_types=1);

use App\Identity\Application\Command\UpdateUserCommand;
use App\Identity\Application\CommandHandler\UpdateUserHandler;
use App\Identity\Application\DTO\UpdateUserInput;
use App\Identity\Application\DTO\UserOutput;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function stubUpdateUserRepo(?User $user = null): UserRepositoryInterface
{
    return new class ($user) implements UserRepositoryInterface {
        public ?User $saved = null;
        public function __construct(private readonly ?User $user)
        {
        }
        public function findById(Uuid $id): ?User
        {
            return $this->user;
        }
        public function findByEmail(string $email): ?User
        {
            return null;
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
            $this->saved = $user;
        }
    };
}

function stubUpdateEventBus(): MessageBusInterface
{
    return new class () implements MessageBusInterface {
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            return new Envelope($message);
        }
    };
}

describe('UpdateUserHandler', function () {
    it('updates a user successfully', function () {
        $user = User::create(
            email: 'john@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        $userId = $user->getId()->toRfc4122();
        $repo = \stubUpdateUserRepo($user);
        $handler = new UpdateUserHandler($repo, \stubUpdateEventBus());

        $input = new UpdateUserInput(firstName: 'Jane', lastName: 'Smith');
        $result = $handler(new UpdateUserCommand($userId, $input));

        expect($result)->toBeInstanceOf(UserOutput::class);
        expect($result->firstName)->toBe('Jane');
        expect($result->lastName)->toBe('Smith');
        expect($repo->saved)->not->toBeNull();
    });

    it('throws not found when user does not exist', function () {
        $handler = new UpdateUserHandler(\stubUpdateUserRepo(null), \stubUpdateEventBus());

        $input = new UpdateUserInput(firstName: 'Jane');
        $handler(new UpdateUserCommand('00000000-0000-0000-0000-000000000000', $input));
    })->throws(NotFoundException::class);
});

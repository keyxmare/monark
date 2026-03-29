<?php

declare(strict_types=1);

namespace App\Identity\Application\CommandHandler;

use App\Identity\Application\Command\RegisterUserCommand;
use App\Identity\Application\DTO\UserOutput;
use App\Identity\Domain\Event\UserCreated;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): UserOutput
    {
        $input = $command->input;

        $existingUser = $this->userRepository->findByEmail($input->email);
        if ($existingUser !== null) {
            throw new class ('A user with this email already exists.') extends DomainException {};
        }

        // Create with a temporary password; the hasher needs the UserInterface instance.
        $user = User::create(
            email: $input->email,
            hashedPassword: 'temporary',
            firstName: $input->firstName,
            lastName: $input->lastName,
        );

        $hashedPassword = $this->passwordHasher->hashPassword($user, $input->password);
        $user->updatePassword($hashedPassword);

        $this->userRepository->save($user);

        $this->eventBus->dispatch(new UserCreated(
            userId: $user->getId()->toRfc4122(),
            email: $user->getEmail(),
        ));

        return UserOutput::fromEntity($user);
    }
}

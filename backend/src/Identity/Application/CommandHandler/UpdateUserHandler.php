<?php

declare(strict_types=1);

namespace App\Identity\Application\CommandHandler;

use App\Identity\Application\Command\UpdateUserCommand;
use App\Identity\Application\DTO\UserOutput;
use App\Identity\Domain\Event\UserUpdated;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): UserOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($command->userId));
        if ($user === null) {
            throw NotFoundException::forEntity('User', $command->userId);
        }

        $input = $command->input;

        $user->update(
            firstName: $input->firstName,
            lastName: $input->lastName,
            avatar: $input->avatar,
            email: $input->email,
        );

        $this->userRepository->save($user);

        $this->eventBus->dispatch(new UserUpdated(
            userId: $user->getId()->toRfc4122(),
        ));

        return UserOutput::fromEntity($user);
    }
}

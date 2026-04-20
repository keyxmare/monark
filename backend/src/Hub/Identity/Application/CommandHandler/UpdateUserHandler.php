<?php

declare(strict_types=1);

namespace App\Hub\Identity\Application\CommandHandler;

use App\Hub\Identity\Application\Command\UpdateUserCommand;
use App\Hub\Identity\Application\DTO\UserOutput;
use App\Hub\Identity\Application\Mapper\UserMapper;
use App\Hub\Identity\Domain\Event\UserUpdated;
use App\Hub\Identity\Domain\Repository\UserRepositoryInterface;
use App\Hub\Shared\Domain\Exception\NotFoundException;
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

        return UserMapper::toOutput($user);
    }
}

<?php

declare(strict_types=1);

namespace App\Hub\Identity\Application\CommandHandler;

use App\Hub\Identity\Application\Command\LoginCommand;
use App\Hub\Identity\Application\DTO\AuthTokenOutput;
use App\Hub\Identity\Application\Mapper\UserMapper;
use App\Hub\Identity\Domain\Repository\UserRepositoryInterface;
use App\Hub\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class LoginHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(LoginCommand $command): AuthTokenOutput
    {
        $input = $command->input;

        $user = $this->userRepository->findByEmail($input->email);
        if ($user === null) {
            throw new class ('Invalid credentials.') extends DomainException {};
        }

        if (!$this->passwordHasher->isPasswordValid($user, $input->password)) {
            throw new class ('Invalid credentials.') extends DomainException {};
        }

        $token = \bin2hex(\random_bytes(32));

        return new AuthTokenOutput(
            token: $token,
            user: UserMapper::toOutput($user),
        );
    }
}

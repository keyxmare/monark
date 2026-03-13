<?php

declare(strict_types=1);

namespace App\Identity\Application\CommandHandler;

use App\Identity\Application\Command\CreateAccessTokenCommand;
use App\Identity\Application\DTO\AccessTokenOutput;
use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\TokenProvider;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateAccessTokenHandler
{
    public function __construct(
        private AccessTokenRepositoryInterface $accessTokenRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(CreateAccessTokenCommand $command): AccessTokenOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($command->userId));
        if ($user === null) {
            throw NotFoundException::forEntity('User', $command->userId);
        }

        $input = $command->input;

        $expiresAt = $input->expiresAt !== null
            ? new DateTimeImmutable($input->expiresAt)
            : null;

        $accessToken = AccessToken::create(
            provider: TokenProvider::from($input->provider),
            token: $input->token,
            scopes: $input->scopes,
            expiresAt: $expiresAt,
            user: $user,
        );

        $this->accessTokenRepository->save($accessToken);

        return AccessTokenOutput::fromEntity($accessToken);
    }
}

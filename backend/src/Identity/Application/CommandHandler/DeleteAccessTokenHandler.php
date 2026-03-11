<?php

declare(strict_types=1);

namespace App\Identity\Application\CommandHandler;

use App\Identity\Application\Command\DeleteAccessTokenCommand;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteAccessTokenHandler
{
    public function __construct(
        private AccessTokenRepositoryInterface $accessTokenRepository,
    ) {
    }

    public function __invoke(DeleteAccessTokenCommand $command): void
    {
        $token = $this->accessTokenRepository->findById(Uuid::fromString($command->tokenId));
        if ($token === null) {
            throw NotFoundException::forEntity('AccessToken', $command->tokenId);
        }

        $this->accessTokenRepository->delete($token);
    }
}

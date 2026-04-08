<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\DeleteProviderCommand;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteProviderHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
    ) {
    }

    public function __invoke(DeleteProviderCommand $command): void
    {
        $provider = $this->providerRepository->findById(Uuid::fromString($command->providerId));
        if ($provider === null) {
            throw NotFoundException::forEntity('Provider', $command->providerId);
        }

        $this->providerRepository->remove($provider);
    }
}

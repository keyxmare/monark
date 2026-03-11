<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\TestProviderConnectionCommand;
use App\Catalog\Application\DTO\ProviderOutput;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class TestProviderConnectionHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
        private GitProviderFactory $gitProviderFactory,
    ) {
    }

    public function __invoke(TestProviderConnectionCommand $command): ProviderOutput
    {
        $provider = $this->providerRepository->findById(Uuid::fromString($command->providerId));
        if ($provider === null) {
            throw NotFoundException::forEntity('Provider', $command->providerId);
        }

        $client = $this->gitProviderFactory->create($provider);

        if ($client->testConnection($provider)) {
            $provider->markConnected();
        } else {
            $provider->markError();
        }

        $this->providerRepository->save($provider);

        return ProviderOutput::fromEntity($provider);
    }
}

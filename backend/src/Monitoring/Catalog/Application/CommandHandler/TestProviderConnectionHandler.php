<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\CommandHandler;

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\Command\TestProviderConnectionCommand;
use App\Monitoring\Catalog\Application\DTO\ProviderOutput;
use App\Monitoring\Catalog\Application\Mapper\ProviderMapper;
use App\Monitoring\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProviderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class TestProviderConnectionHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
        private GitProviderFactoryInterface $gitProviderFactory,
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

        return ProviderMapper::toOutput($provider);
    }
}

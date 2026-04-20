<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\CommandHandler;

use App\Monitoring\Catalog\Application\Command\CreateProviderCommand;
use App\Monitoring\Catalog\Application\DTO\ProviderOutput;
use App\Monitoring\Catalog\Application\Mapper\ProviderMapper;
use App\Monitoring\Catalog\Domain\Model\Provider;
use App\Monitoring\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProviderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateProviderHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
        private GitProviderFactoryInterface $gitProviderFactory,
    ) {
    }

    public function __invoke(CreateProviderCommand $command): ProviderOutput
    {
        $input = $command->input;

        $provider = Provider::create(
            name: $input->name,
            type: $input->type,
            url: $input->url,
            apiToken: $input->apiToken,
            username: $input->username,
        );

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

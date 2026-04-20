<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\CommandHandler;

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\Command\UpdateProviderCommand;
use App\Monitoring\Catalog\Application\DTO\ProviderOutput;
use App\Monitoring\Catalog\Application\Mapper\ProviderMapper;
use App\Monitoring\Catalog\Domain\Repository\ProviderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateProviderHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
    ) {
    }

    public function __invoke(UpdateProviderCommand $command): ProviderOutput
    {
        $provider = $this->providerRepository->findById(Uuid::fromString($command->providerId));
        if ($provider === null) {
            throw NotFoundException::forEntity('Provider', $command->providerId);
        }

        $input = $command->input;

        $provider->update(
            name: $input->name,
            url: $input->url,
            apiToken: $input->apiToken,
            username: $input->username,
        );

        $this->providerRepository->save($provider);

        return ProviderMapper::toOutput($provider);
    }
}

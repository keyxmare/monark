<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\UpdateProviderCommand;
use App\Catalog\Application\DTO\ProviderOutput;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
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
        );

        $this->providerRepository->save($provider);

        return ProviderOutput::fromEntity($provider);
    }
}

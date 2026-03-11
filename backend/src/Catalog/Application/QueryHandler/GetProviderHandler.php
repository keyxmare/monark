<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\ProviderOutput;
use App\Catalog\Application\Query\GetProviderQuery;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProviderHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
    ) {
    }

    public function __invoke(GetProviderQuery $query): ProviderOutput
    {
        $provider = $this->providerRepository->findById(Uuid::fromString($query->providerId));
        if ($provider === null) {
            throw NotFoundException::forEntity('Provider', $query->providerId);
        }

        return ProviderOutput::fromEntity($provider);
    }
}

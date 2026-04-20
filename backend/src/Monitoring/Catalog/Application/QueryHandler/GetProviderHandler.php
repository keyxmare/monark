<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\QueryHandler;

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\DTO\ProviderOutput;
use App\Monitoring\Catalog\Application\Mapper\ProviderMapper;
use App\Monitoring\Catalog\Application\Query\GetProviderQuery;
use App\Monitoring\Catalog\Domain\Repository\ProviderRepositoryInterface;
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

        return ProviderMapper::toOutput($provider);
    }
}

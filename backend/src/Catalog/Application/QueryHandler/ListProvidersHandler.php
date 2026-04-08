<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\ProviderListOutput;
use App\Catalog\Application\Mapper\ProviderMapper;
use App\Catalog\Application\Query\ListProvidersQuery;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListProvidersHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
    ) {
    }

    public function __invoke(ListProvidersQuery $query): ProviderListOutput
    {
        $providers = $this->providerRepository->findAll($query->page, $query->perPage);
        $total = $this->providerRepository->count();

        $items = \array_map(
            static fn ($provider) => ProviderMapper::toOutput($provider),
            $providers,
        );

        return new ProviderListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}

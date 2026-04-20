<?php

declare(strict_types=1);

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\DTO\ProviderOutput;
use App\Monitoring\Catalog\Application\Query\GetProviderQuery;
use App\Monitoring\Catalog\Application\Query\ListProvidersQuery;
use App\Monitoring\Catalog\Application\QueryHandler\GetProviderHandler;
use App\Monitoring\Catalog\Application\QueryHandler\ListProvidersHandler;
use App\Monitoring\Catalog\Domain\Model\Provider;
use App\Monitoring\Catalog\Domain\Model\ProviderType;
use App\Monitoring\Catalog\Domain\Repository\ProviderRepositoryInterface;

function stubProviderQueryRepo(?Provider $provider = null): ProviderRepositoryInterface
{
    return new class ($provider) implements ProviderRepositoryInterface {
        public function __construct(private readonly ?Provider $provider)
        {
        }

        public function findById(\Symfony\Component\Uid\Uuid $id): ?Provider
        {
            return $this->provider;
        }

        /** @return list<Provider> */
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->provider ? [$this->provider] : [];
        }

        public function count(): int
        {
            return $this->provider ? 1 : 0;
        }

        public function save(Provider $provider): void
        {
        }

        public function remove(Provider $provider): void
        {
        }
    };
}

describe('GetProviderHandler', function () {
    it('returns provider output', function () {
        $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token');
        $handler = new GetProviderHandler(\stubProviderQueryRepo($provider));

        $result = $handler(new GetProviderQuery($provider->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(ProviderOutput::class);
        expect($result->name)->toBe('GitLab');
    });

    it('throws not found for unknown provider', function () {
        $handler = new GetProviderHandler(\stubProviderQueryRepo(null));

        $handler(new GetProviderQuery('a0000000-0000-0000-0000-000000000001'));
    })->throws(NotFoundException::class);
});

describe('ListProvidersHandler', function () {
    it('returns paginated providers', function () {
        $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token');
        $handler = new ListProvidersHandler(\stubProviderQueryRepo($provider));

        $result = $handler(new ListProvidersQuery(1, 20));

        expect($result->pagination->total)->toBe(1);
        expect($result->pagination->items)->toHaveCount(1);
        expect($result->pagination->items[0])->toBeInstanceOf(ProviderOutput::class);
    });

    it('returns empty list', function () {
        $handler = new ListProvidersHandler(\stubProviderQueryRepo(null));

        $result = $handler(new ListProvidersQuery(1, 20));

        expect($result->pagination->total)->toBe(0);
        expect($result->pagination->items)->toBeEmpty();
    });
});

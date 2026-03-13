<?php

declare(strict_types=1);

use App\Catalog\Application\Command\DeleteProviderCommand;
use App\Catalog\Application\Command\TestProviderConnectionCommand;
use App\Catalog\Application\Command\UpdateProviderCommand;
use App\Catalog\Application\CommandHandler\DeleteProviderHandler;
use App\Catalog\Application\CommandHandler\TestProviderConnectionHandler;
use App\Catalog\Application\CommandHandler\UpdateProviderHandler;
use App\Catalog\Application\DTO\ProviderOutput;
use App\Catalog\Application\DTO\UpdateProviderInput;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use App\Shared\Domain\Exception\NotFoundException;

function stubProviderHandlerRepo(?Provider $provider = null): ProviderRepositoryInterface
{
    return new class ($provider) implements ProviderRepositoryInterface {
        public bool $removeCalled = false;
        public bool $saveCalled = false;

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
            $this->saveCalled = true;
        }

        public function remove(Provider $provider): void
        {
            $this->removeCalled = true;
        }
    };
}

describe('DeleteProviderHandler', function () {
    it('deletes a provider', function () {
        $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token');
        $repo = \stubProviderHandlerRepo($provider);
        $handler = new DeleteProviderHandler($repo);

        $handler(new DeleteProviderCommand($provider->getId()->toRfc4122()));

        expect($repo->removeCalled)->toBeTrue();
    });

    it('throws not found for unknown provider', function () {
        $repo = \stubProviderHandlerRepo(null);
        $handler = new DeleteProviderHandler($repo);

        $handler(new DeleteProviderCommand('a0000000-0000-0000-0000-000000000001'));
    })->throws(NotFoundException::class);
});

describe('UpdateProviderHandler', function () {
    it('updates a provider and returns output', function () {
        $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token');
        $repo = \stubProviderHandlerRepo($provider);
        $handler = new UpdateProviderHandler($repo);

        $result = $handler(new UpdateProviderCommand(
            $provider->getId()->toRfc4122(),
            new UpdateProviderInput(name: 'Updated GitLab'),
        ));

        expect($result)->toBeInstanceOf(ProviderOutput::class);
        expect($result->name)->toBe('Updated GitLab');
        expect($repo->saveCalled)->toBeTrue();
    });

    it('throws not found for unknown provider', function () {
        $repo = \stubProviderHandlerRepo(null);
        $handler = new UpdateProviderHandler($repo);

        $handler(new UpdateProviderCommand('a0000000-0000-0000-0000-000000000001', new UpdateProviderInput()));
    })->throws(NotFoundException::class);
});

describe('TestProviderConnectionHandler', function () {
    it('marks provider connected on success', function () {
        $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token');
        $repo = \stubProviderHandlerRepo($provider);

        $gitClient = $this->createMock(GitProviderInterface::class);
        $gitClient->method('testConnection')->willReturn(true);

        $factory = $this->createMock(GitProviderFactory::class);
        $factory->method('create')->willReturn($gitClient);

        $handler = new TestProviderConnectionHandler($repo, $factory);
        $result = $handler(new TestProviderConnectionCommand($provider->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(ProviderOutput::class);
        expect($result->status)->toBe('connected');
        expect($repo->saveCalled)->toBeTrue();
    });

    it('marks provider error on failure', function () {
        $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token');
        $repo = \stubProviderHandlerRepo($provider);

        $gitClient = $this->createMock(GitProviderInterface::class);
        $gitClient->method('testConnection')->willReturn(false);

        $factory = $this->createMock(GitProviderFactory::class);
        $factory->method('create')->willReturn($gitClient);

        $handler = new TestProviderConnectionHandler($repo, $factory);
        $result = $handler(new TestProviderConnectionCommand($provider->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(ProviderOutput::class);
        expect($result->status)->toBe('error');
    });

    it('throws not found for unknown provider', function () {
        $repo = \stubProviderHandlerRepo(null);
        $factory = $this->createMock(GitProviderFactory::class);
        $handler = new TestProviderConnectionHandler($repo, $factory);

        $handler(new TestProviderConnectionCommand('a0000000-0000-0000-0000-000000000001'));
    })->throws(NotFoundException::class);
});

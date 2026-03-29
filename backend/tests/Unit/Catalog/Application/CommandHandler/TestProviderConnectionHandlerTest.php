<?php

declare(strict_types=1);

use App\Catalog\Application\Command\TestProviderConnectionCommand;
use App\Catalog\Application\CommandHandler\TestProviderConnectionHandler;
use App\Catalog\Application\DTO\ProviderOutput;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubTestConnectionProviderRepo(?Provider $findByIdResult = null): ProviderRepositoryInterface
{
    return new class ($findByIdResult) implements ProviderRepositoryInterface {
        public ?Provider $saved = null;

        public function __construct(private readonly ?Provider $findByIdResult)
        {
        }

        public function findById(Uuid $id): ?Provider
        {
            return $this->findByIdResult;
        }

        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }

        public function count(): int
        {
            return 0;
        }

        public function save(Provider $provider): void
        {
            $this->saved = $provider;
        }

        public function remove(Provider $provider): void
        {
        }
    };
}

function stubTestConnectionGitProviderFactory(bool $connectionResult): GitProviderFactoryInterface
{
    return new class ($connectionResult) implements GitProviderFactoryInterface {
        public function __construct(private readonly bool $connectionResult)
        {
        }

        public function create(Provider $provider): GitProviderInterface
        {
            $result = $this->connectionResult;

            return new class ($result) implements GitProviderInterface {
                public function __construct(private readonly bool $connectionResult)
                {
                }

                public function listProjects(Provider $provider, int $page = 1, int $perPage = 20, ?string $search = null, ?string $visibility = null, string $sort = 'name', string $sortDir = 'asc'): array
                {
                    return [];
                }

                public function countProjects(Provider $provider, ?string $search = null, ?string $visibility = null): int
                {
                    return 0;
                }

                public function testConnection(Provider $provider): bool
                {
                    return $this->connectionResult;
                }

                public function getProject(Provider $provider, string $externalId): \App\Catalog\Domain\Model\RemoteProject
                {
                    throw new \RuntimeException('Not implemented');
                }

                public function getFileContent(Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string
                {
                    return null;
                }

                public function listDirectory(Provider $provider, string $externalProjectId, string $path = '', string $ref = 'main'): array
                {
                    return [];
                }

                public function listMergeRequests(Provider $provider, string $externalProjectId, ?string $state = null, int $page = 1, int $perPage = 20, ?\DateTimeImmutable $updatedAfter = null): array
                {
                    return [];
                }
            };
        }
    };
}

describe('TestProviderConnectionHandler', function () {
    it('marks provider as connected when connection succeeds', function () {
        $provider = Provider::create(
            name: 'My GitLab',
            type: ProviderType::GitLab,
            url: 'https://gitlab.com',
            apiToken: 'test-token',
        );
        $repo = \stubTestConnectionProviderRepo($provider);
        $factory = \stubTestConnectionGitProviderFactory(true);
        $handler = new TestProviderConnectionHandler($repo, $factory);

        $command = new TestProviderConnectionCommand($provider->getId()->toRfc4122());
        $result = $handler($command);

        expect($result)->toBeInstanceOf(ProviderOutput::class);
        expect($result->status)->toBe('connected');
        expect($result->name)->toBe('My GitLab');
        expect($repo->saved)->toBe($provider);
    });

    it('marks provider as error when connection fails', function () {
        $provider = Provider::create(
            name: 'My GitLab',
            type: ProviderType::GitLab,
            url: 'https://gitlab.com',
            apiToken: 'bad-token',
        );
        $repo = \stubTestConnectionProviderRepo($provider);
        $factory = \stubTestConnectionGitProviderFactory(false);
        $handler = new TestProviderConnectionHandler($repo, $factory);

        $command = new TestProviderConnectionCommand($provider->getId()->toRfc4122());
        $result = $handler($command);

        expect($result)->toBeInstanceOf(ProviderOutput::class);
        expect($result->status)->toBe('error');
        expect($repo->saved)->toBe($provider);
    });

    it('throws NotFoundException when provider does not exist', function () {
        $repo = \stubTestConnectionProviderRepo(null);
        $factory = \stubTestConnectionGitProviderFactory(true);
        $handler = new TestProviderConnectionHandler($repo, $factory);

        $command = new TestProviderConnectionCommand(Uuid::v7()->toRfc4122());
        $handler($command);
    })->throws(NotFoundException::class);
});

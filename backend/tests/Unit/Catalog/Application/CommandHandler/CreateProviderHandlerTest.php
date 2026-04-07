<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreateProviderCommand;
use App\Catalog\Application\CommandHandler\CreateProviderHandler;
use App\Catalog\Application\DTO\CreateProviderInput;
use App\Catalog\Application\DTO\ProviderOutput;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubProviderRepo(): ProviderRepositoryInterface
{
    return new class () implements ProviderRepositoryInterface {
        public ?Provider $saved = null;
        public function findById(Uuid $id): ?Provider
        {
            return null;
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

function stubGitProviderFactory(bool $connectionSuccess): GitProviderFactoryInterface
{
    $gitClient = new class ($connectionSuccess) implements GitProviderInterface {
        public function __construct(private readonly bool $success)
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
            return $this->success;
        }
        public function getProject(Provider $provider, string $externalId): RemoteProject
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
        public function listBranches(Provider $provider, string $externalProjectId): array
        {
            return [];
        }
        public function listCommits(Provider $provider, string $externalProjectId, string $ref, ?\DateTimeImmutable $since = null, ?\DateTimeImmutable $until = null, int $perPage = 100): array
        {
            return [];
        }
    };

    $factory = new class ($gitClient) implements GitProviderFactoryInterface {
        private GitProviderInterface $client;
        public function __construct(GitProviderInterface $client)
        {
            $this->client = $client;
        }
        public function create(Provider $provider): GitProviderInterface
        {
            return $this->client;
        }
    };

    return $factory;
}

describe('CreateProviderHandler', function () {
    it('creates a provider and marks connected on successful test', function () {
        $repo = \stubProviderRepo();
        $factory = \stubGitProviderFactory(true);
        $handler = new CreateProviderHandler($repo, $factory);

        $input = new CreateProviderInput(
            name: 'GitLab Prod',
            type: ProviderType::GitLab,
            url: 'https://gitlab.example.com',
            apiToken: 'glpat-test-token',
        );

        $result = $handler(new CreateProviderCommand($input));

        expect($result)->toBeInstanceOf(ProviderOutput::class);
        expect($result->name)->toBe('GitLab Prod');
        expect($result->type)->toBe('gitlab');
        expect($result->status)->toBe('connected');
        expect($result->url)->toBe('https://gitlab.example.com');
        expect($result->lastSyncAt)->not->toBeNull();
        expect($repo->saved)->not->toBeNull();
    });

    it('creates a provider and marks error on failed test', function () {
        $repo = \stubProviderRepo();
        $factory = \stubGitProviderFactory(false);
        $handler = new CreateProviderHandler($repo, $factory);

        $input = new CreateProviderInput(
            name: 'GitLab Broken',
            type: ProviderType::GitLab,
            url: 'https://bad-gitlab.example.com',
            apiToken: 'bad-token',
        );

        $result = $handler(new CreateProviderCommand($input));

        expect($result)->toBeInstanceOf(ProviderOutput::class);
        expect($result->status)->toBe('error');
        expect($result->lastSyncAt)->toBeNull();
    });

    it('trims trailing slash from url', function () {
        $repo = \stubProviderRepo();
        $factory = \stubGitProviderFactory(true);
        $handler = new CreateProviderHandler($repo, $factory);

        $input = new CreateProviderInput(
            name: 'GitLab',
            type: ProviderType::GitLab,
            url: 'https://gitlab.example.com/',
            apiToken: 'token',
        );

        $result = $handler(new CreateProviderCommand($input));

        expect($result->url)->toBe('https://gitlab.example.com');
    });
});

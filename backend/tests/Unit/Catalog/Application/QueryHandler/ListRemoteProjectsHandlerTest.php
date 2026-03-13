<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\RemoteProjectListOutput;
use App\Catalog\Application\Query\ListRemoteProjectsQuery;
use App\Catalog\Application\QueryHandler\ListRemoteProjectsHandler;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function stubRemoteProviderRepo(?Provider $provider = null): ProviderRepositoryInterface
{
    return new class ($provider) implements ProviderRepositoryInterface {
        public function __construct(private readonly ?Provider $provider)
        {
        }
        public function findById(Uuid $id): ?Provider
        {
            return $this->provider;
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
        }
        public function remove(Provider $provider): void
        {
        }
    };
}

function stubRemoteProjectRepo(array $importedExternalIds = []): ProjectRepositoryInterface
{
    return new class ($importedExternalIds) implements ProjectRepositoryInterface {
        public function __construct(private readonly array $importedExternalIds)
        {
        }
        public function findById(Uuid $id): ?Project
        {
            return null;
        }
        public function findBySlug(string $slug): ?Project
        {
            return null;
        }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
        {
            return null;
        }
        public function findExternalIdMapByProvider(Uuid $providerId): array
        {
            return $this->importedExternalIds;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByProviderId(Uuid $providerId): array
        {
            return [];
        }
        public function findAllWithProvider(): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(Project $project): void
        {
        }
        public function delete(Project $project): void
        {
        }
    };
}

function stubRemoteGitFactory(array $remoteProjects): GitProviderFactoryInterface
{
    $gitClient = new class ($remoteProjects) implements GitProviderInterface {
        public function __construct(private readonly array $projects)
        {
        }
        public function listProjects(Provider $provider, int $page = 1, int $perPage = 20, ?string $search = null, ?string $visibility = null, string $sort = 'name', string $sortDir = 'asc'): array
        {
            return $this->projects;
        }
        public function countProjects(Provider $provider, ?string $search = null, ?string $visibility = null): int
        {
            return \count($this->projects);
        }
        public function testConnection(Provider $provider): bool
        {
            return true;
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
        public function listMergeRequests(Provider $provider, string $externalProjectId, ?string $state = null, int $page = 1, int $perPage = 20, ?\DateTimeImmutable $updatedAfter = null): array
        {
            return [];
        }
    };

    return new class ($gitClient) implements GitProviderFactoryInterface {
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
}

describe('ListRemoteProjectsHandler', function () {
    it('returns remote projects with already-imported flag', function () {
        $provider = ProviderFactory::create();
        $localUuidA = Uuid::v7()->toRfc4122();
        $localUuidC = Uuid::v7()->toRfc4122();
        $remoteProjects = [
            new RemoteProject('10', 'Project A', 'team/project-a', 'Desc A', 'https://gl.com/a.git', 'main', 'public', null),
            new RemoteProject('20', 'Project B', 'team/project-b', null, 'https://gl.com/b.git', 'develop', 'private', null),
            new RemoteProject('30', 'Project C', 'team/project-c', 'Desc C', 'https://gl.com/c.git', 'main', 'internal', null),
        ];

        $providerRepo = \stubRemoteProviderRepo($provider);
        $projectRepo = \stubRemoteProjectRepo(['10' => $localUuidA, '30' => $localUuidC]);
        $factory = \stubRemoteGitFactory($remoteProjects);

        $handler = new ListRemoteProjectsHandler($providerRepo, $projectRepo, $factory);
        $result = $handler(new ListRemoteProjectsQuery($provider->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(RemoteProjectListOutput::class);
        $items = $result->pagination->items;
        expect($items)->toHaveCount(3);
        expect($items[0]->externalId)->toBe('10');
        expect($items[0]->alreadyImported)->toBeTrue();
        expect($items[0]->localProjectId)->toBe($localUuidA);
        expect($items[1]->externalId)->toBe('20');
        expect($items[1]->alreadyImported)->toBeFalse();
        expect($items[1]->localProjectId)->toBeNull();
        expect($items[2]->externalId)->toBe('30');
        expect($items[2]->alreadyImported)->toBeTrue();
        expect($items[2]->localProjectId)->toBe($localUuidC);
    });

    it('throws not found for unknown provider', function () {
        $providerRepo = \stubRemoteProviderRepo(null);
        $projectRepo = \stubRemoteProjectRepo();
        $factory = \stubRemoteGitFactory([]);

        $handler = new ListRemoteProjectsHandler($providerRepo, $projectRepo, $factory);
        $handler(new ListRemoteProjectsQuery(Uuid::v7()->toRfc4122()));
    })->throws(\DomainException::class);
});

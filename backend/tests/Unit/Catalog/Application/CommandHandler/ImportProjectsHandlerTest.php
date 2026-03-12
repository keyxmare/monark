<?php

declare(strict_types=1);

use App\Catalog\Application\Command\ImportProjectsCommand;
use App\Catalog\Application\CommandHandler\ImportProjectsHandler;
use App\Catalog\Application\DTO\ImportProjectItem;
use App\Catalog\Application\DTO\ImportProjectsInput;
use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function stubImportProviderRepo(?Provider $provider = null): ProviderRepositoryInterface
{
    return new class ($provider) implements ProviderRepositoryInterface {
        public function __construct(private readonly ?Provider $provider) {}
        public function findById(Uuid $id): ?Provider { return $this->provider; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Provider $provider): void {}
        public function remove(Provider $provider): void {}
    };
}

function stubImportProjectRepo(array $existingExternalIds = [], array $existingSlugs = []): ProjectRepositoryInterface
{
    return new class ($existingExternalIds, $existingSlugs) implements ProjectRepositoryInterface {
        /** @var list<Project> */
        public array $saved = [];
        public function __construct(
            private readonly array $existingExternalIds,
            private readonly array $existingSlugs,
        ) {}
        public function findById(Uuid $id): ?Project { return null; }
        public function findBySlug(string $slug): ?Project
        {
            return \in_array($slug, $this->existingSlugs, true) ? Project::create(
                name: 'existing', slug: $slug, description: null,
                repositoryUrl: 'https://test.com', defaultBranch: 'main',
                visibility: \App\Catalog\Domain\Model\ProjectVisibility::Private,
                ownerId: Uuid::v7(),
            ) : null;
        }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
        {
            return \in_array($externalId, $this->existingExternalIds, true) ? Project::create(
                name: 'existing', slug: 'existing', description: null,
                repositoryUrl: 'https://test.com', defaultBranch: 'main',
                visibility: \App\Catalog\Domain\Model\ProjectVisibility::Private,
                ownerId: Uuid::v7(),
            ) : null;
        }
        public function findExternalIdMapByProvider(Uuid $providerId): array { return $this->existingExternalIds; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProviderId(Uuid $providerId): array { return []; }
        public function findAllWithProvider(): array { return []; }
        public function count(): int { return 0; }
        public function save(Project $project): void { $this->saved[] = $project; }
        public function delete(Project $project): void {}
    };
}

describe('ImportProjectsHandler', function () {
    it('imports remote projects as local projects', function () {
        $provider = ProviderFactory::create();
        $providerRepo = stubImportProviderRepo($provider);
        $projectRepo = stubImportProjectRepo();
        $handler = new ImportProjectsHandler($providerRepo, $projectRepo);
        $ownerId = Uuid::v7()->toRfc4122();

        $input = new ImportProjectsInput(
            projects: [
                new ImportProjectItem(
                    externalId: '42',
                    name: 'My App',
                    slug: 'team/my-app',
                    description: 'A cool app',
                    repositoryUrl: 'https://gitlab.example.com/team/my-app.git',
                    defaultBranch: 'main',
                    visibility: 'private',
                ),
                new ImportProjectItem(
                    externalId: '43',
                    name: 'Another App',
                    slug: 'team/another-app',
                    repositoryUrl: 'https://gitlab.example.com/team/another-app.git',
                ),
            ],
        );

        $result = $handler(new ImportProjectsCommand($provider->getId()->toRfc4122(), $input, $ownerId));

        expect($result)->toHaveCount(2);
        expect($result[0])->toBeInstanceOf(ProjectOutput::class);
        expect($result[0]->name)->toBe('My App');
        expect($result[0]->externalId)->toBe('42');
        expect($result[0]->providerId)->toBe($provider->getId()->toRfc4122());
        expect($projectRepo->saved)->toHaveCount(2);
    });

    it('skips already imported projects', function () {
        $provider = ProviderFactory::create();
        $providerRepo = stubImportProviderRepo($provider);
        $projectRepo = stubImportProjectRepo(existingExternalIds: ['42']);
        $handler = new ImportProjectsHandler($providerRepo, $projectRepo);
        $ownerId = Uuid::v7()->toRfc4122();

        $input = new ImportProjectsInput(
            projects: [
                new ImportProjectItem(
                    externalId: '42',
                    name: 'Already Imported',
                    slug: 'team/already-imported',
                    repositoryUrl: 'https://gitlab.example.com/team/already-imported.git',
                ),
                new ImportProjectItem(
                    externalId: '99',
                    name: 'New Project',
                    slug: 'team/new-project',
                    repositoryUrl: 'https://gitlab.example.com/team/new-project.git',
                ),
            ],
        );

        $result = $handler(new ImportProjectsCommand($provider->getId()->toRfc4122(), $input, $ownerId));

        expect($result)->toHaveCount(1);
        expect($result[0]->name)->toBe('New Project');
    });

    it('throws not found for unknown provider', function () {
        $providerRepo = stubImportProviderRepo(null);
        $projectRepo = stubImportProjectRepo();
        $handler = new ImportProjectsHandler($providerRepo, $projectRepo);

        $input = new ImportProjectsInput(
            projects: [
                new ImportProjectItem(
                    externalId: '1',
                    name: 'test',
                    slug: 'test',
                    repositoryUrl: 'https://test.com',
                ),
            ],
        );

        $handler(new ImportProjectsCommand(Uuid::v7()->toRfc4122(), $input, Uuid::v7()->toRfc4122()));
    })->throws(\DomainException::class);
});

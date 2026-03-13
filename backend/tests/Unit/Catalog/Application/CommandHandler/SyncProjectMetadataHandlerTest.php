<?php

declare(strict_types=1);

use App\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Catalog\Application\CommandHandler\SyncProjectMetadataHandler;
use App\Catalog\Domain\Event\ProjectMetadataSyncedEvent;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function spyMetadataEventBus(): object
{
    return new class () implements MessageBusInterface {
        /** @var list<object> */
        public array $dispatched = [];
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched[] = $message;
            return Envelope::wrap($message, $stamps);
        }
    };
}

function stubMetadataProjectRepo(?Project $project = null): object
{
    return new class ($project) implements ProjectRepositoryInterface {
        public ?Project $saved = null;
        public function __construct(private readonly ?Project $project)
        {
        }
        public function findById(Uuid $id): ?Project
        {
            return $this->project;
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
            return [];
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
            $this->saved = $project;
        }
        public function delete(Project $project): void
        {
        }
    };
}

function stubMetadataGitProviderFactory(RemoteProject $remoteProject): GitProviderFactory
{
    $client = new class ($remoteProject) implements GitProviderInterface {
        public function __construct(private readonly RemoteProject $remoteProject)
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
            return true;
        }
        public function getProject(Provider $provider, string $externalId): RemoteProject
        {
            return $this->remoteProject;
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

    return new class ($client) extends GitProviderFactory {
        public function __construct(private readonly GitProviderInterface $client)
        {
        }

        public function create(Provider $provider): GitProviderInterface
        {
            return $this->client;
        }
    };
}

function createMetadataProject(Provider $provider, string $name = 'My Project', string $slug = 'my-project', ProjectVisibility $visibility = ProjectVisibility::Private, string $defaultBranch = 'main', ?string $description = 'Original desc'): Project
{
    return Project::create(
        name: $name,
        slug: $slug,
        description: $description,
        repositoryUrl: 'https://gitlab.example.com/' . $slug,
        defaultBranch: $defaultBranch,
        visibility: $visibility,
        ownerId: Uuid::v7(),
        provider: $provider,
        externalId: '42',
    );
}

describe('SyncProjectMetadataHandler', function () {
    it('updates metadata when remote differs', function () {
        $provider = ProviderFactory::create();
        $project = \createMetadataProject($provider);

        $remote = new RemoteProject(
            externalId: '42',
            name: 'Renamed Project',
            slug: 'renamed-project',
            description: 'New desc',
            repositoryUrl: 'https://gitlab.example.com/renamed.git',
            defaultBranch: 'develop',
            visibility: 'public',
            avatarUrl: null,
        );

        $projectRepo = \stubMetadataProjectRepo($project);
        $factory = \stubMetadataGitProviderFactory($remote);
        $eventBus = \spyMetadataEventBus();

        $handler = new SyncProjectMetadataHandler($projectRepo, $factory, $eventBus);
        $handler(new SyncProjectMetadataCommand($project->getId()->toRfc4122()));

        expect($project->getName())->toBe('Renamed Project');
        expect($project->getDescription())->toBe('New desc');
        expect($project->getDefaultBranch())->toBe('develop');
        expect($project->getVisibility())->toBe(ProjectVisibility::Public);
        expect($projectRepo->saved)->toBe($project);
        expect($eventBus->dispatched)->toHaveCount(1);
        expect($eventBus->dispatched[0])->toBeInstanceOf(ProjectMetadataSyncedEvent::class);
        expect($eventBus->dispatched[0]->changedFields)->toContain('name', 'description', 'defaultBranch', 'visibility');
    });

    it('does not emit event when nothing changed', function () {
        $provider = ProviderFactory::create();
        $project = \createMetadataProject($provider);

        $remote = new RemoteProject(
            externalId: '42',
            name: 'My Project',
            slug: 'my-project',
            description: 'Original desc',
            repositoryUrl: 'https://gitlab.example.com/my-project.git',
            defaultBranch: 'main',
            visibility: 'private',
            avatarUrl: null,
        );

        $projectRepo = \stubMetadataProjectRepo($project);
        $factory = \stubMetadataGitProviderFactory($remote);
        $eventBus = \spyMetadataEventBus();

        $handler = new SyncProjectMetadataHandler($projectRepo, $factory, $eventBus);
        $handler(new SyncProjectMetadataCommand($project->getId()->toRfc4122()));

        expect($projectRepo->saved)->toBeNull();
        expect($eventBus->dispatched)->toBeEmpty();
    });

    it('ignores project without provider', function () {
        $project = Project::create(
            name: 'No Provider',
            slug: 'no-provider',
            description: null,
            repositoryUrl: 'https://example.com',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
        );

        $remote = new RemoteProject('1', 'X', 'x', null, 'u', 'main', 'public', null);
        $projectRepo = \stubMetadataProjectRepo($project);
        $factory = \stubMetadataGitProviderFactory($remote);
        $eventBus = \spyMetadataEventBus();

        $handler = new SyncProjectMetadataHandler($projectRepo, $factory, $eventBus);
        $handler(new SyncProjectMetadataCommand($project->getId()->toRfc4122()));

        expect($projectRepo->saved)->toBeNull();
        expect($eventBus->dispatched)->toBeEmpty();
    });

    it('ignores unknown project', function () {
        $remote = new RemoteProject('1', 'X', 'x', null, 'u', 'main', 'public', null);
        $projectRepo = \stubMetadataProjectRepo(null);
        $factory = \stubMetadataGitProviderFactory($remote);
        $eventBus = \spyMetadataEventBus();

        $handler = new SyncProjectMetadataHandler($projectRepo, $factory, $eventBus);
        $handler(new SyncProjectMetadataCommand(Uuid::v7()->toRfc4122()));

        expect($projectRepo->saved)->toBeNull();
        expect($eventBus->dispatched)->toBeEmpty();
    });

    it('only lists actually changed fields', function () {
        $provider = ProviderFactory::create();
        $project = \createMetadataProject($provider);

        $remote = new RemoteProject(
            externalId: '42',
            name: 'My Project',
            slug: 'my-project',
            description: 'Original desc',
            repositoryUrl: 'https://gitlab.example.com/my-project.git',
            defaultBranch: 'develop',
            visibility: 'private',
            avatarUrl: null,
        );

        $projectRepo = \stubMetadataProjectRepo($project);
        $factory = \stubMetadataGitProviderFactory($remote);
        $eventBus = \spyMetadataEventBus();

        $handler = new SyncProjectMetadataHandler($projectRepo, $factory, $eventBus);
        $handler(new SyncProjectMetadataCommand($project->getId()->toRfc4122()));

        expect($eventBus->dispatched[0]->changedFields)->toBe(['defaultBranch']);
        expect($project->getDefaultBranch())->toBe('develop');
        expect($project->getName())->toBe('My Project');
    });
});

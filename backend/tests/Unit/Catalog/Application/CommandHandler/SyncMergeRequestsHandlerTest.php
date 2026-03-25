<?php

declare(strict_types=1);

use App\Catalog\Application\Command\SyncMergeRequestsCommand;
use App\Catalog\Application\CommandHandler\SyncMergeRequestsHandler;
use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteMergeRequest;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Event\MergeRequestsSyncedEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function stubSyncMRProjectRepo(?Project $project = null): ProjectRepositoryInterface
{
    return new class ($project) implements ProjectRepositoryInterface {
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
        }
        public function delete(Project $project): void
        {
        }
    };
}

function stubSyncMRRepo(?MergeRequest $existing = null): object
{
    return new class ($existing) implements MergeRequestRepositoryInterface {
        /** @var list<MergeRequest> */
        public array $saved = [];
        public function __construct(private readonly ?MergeRequest $existing)
        {
        }
        public function findById(Uuid $id): ?MergeRequest
        {
            return null;
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, array $statuses = [], ?string $author = null): array
        {
            return [];
        }
        public function findByExternalIdAndProject(string $externalId, Uuid $projectId): ?MergeRequest
        {
            return $this->existing !== null && $this->existing->getExternalId() === $externalId ? $this->existing : null;
        }
        public function countByProjectId(Uuid $projectId, array $statuses = [], ?string $author = null): int
        {
            return 0;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(MergeRequest $mergeRequest): void
        {
            $this->saved[] = $mergeRequest;
        }
        public function delete(MergeRequest $mergeRequest): void
        {
        }
    };
}

function stubSyncMRGitFactory(array $remoteMRs = []): GitProviderFactoryInterface
{
    $client = new class ($remoteMRs) implements GitProviderInterface {
        public function __construct(private readonly array $mrs)
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
            return $this->mrs;
        }
    };

    return new class ($client) implements GitProviderFactoryInterface {
        public function __construct(private readonly GitProviderInterface $client)
        {
        }
        public function create(Provider $provider): GitProviderInterface
        {
            return $this->client;
        }
    };
}

function spySyncMREventBus(): object
{
    return new class () implements MessageBusInterface {
        /** @var list<object> */
        public array $dispatched = [];
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $msg = $message instanceof Envelope ? $message->getMessage() : $message;
            $this->dispatched[] = $msg;
            return Envelope::wrap($message, $stamps);
        }
    };
}

function createSyncMRProject(Provider $provider): Project
{
    return Project::create(
        name: 'Test Project',
        slug: 'test-project',
        description: null,
        repositoryUrl: 'https://gitlab.example.com/test/project',
        defaultBranch: 'main',
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
        provider: $provider,
        externalId: '42',
    );
}

function makeRemoteMR(string $externalId = '1', string $status = 'open', string $title = 'MR Title'): RemoteMergeRequest
{
    return new RemoteMergeRequest(
        externalId: $externalId,
        title: $title,
        description: 'Description',
        sourceBranch: 'feature/test',
        targetBranch: 'main',
        status: $status,
        author: 'dev',
        url: 'https://gitlab.example.com/test/project/-/merge_requests/' . $externalId,
        additions: 10,
        deletions: 5,
        reviewers: ['alice'],
        labels: ['feature'],
        createdAt: '2026-03-10T10:00:00Z',
        updatedAt: '2026-03-11T14:00:00Z',
        mergedAt: null,
        closedAt: null,
    );
}

describe('SyncMergeRequestsHandler', function () {
    it('creates new merge requests from remote', function () {
        $provider = ProviderFactory::create();
        $project = \createSyncMRProject($provider);
        $remoteMRs = [\makeRemoteMR('1'), \makeRemoteMR('2', 'draft', 'Draft MR')];

        $mrRepo = \stubSyncMRRepo();
        $eventBus = \spySyncMREventBus();

        $handler = new SyncMergeRequestsHandler(
            \stubSyncMRProjectRepo($project),
            $mrRepo,
            \stubSyncMRGitFactory($remoteMRs),
            $eventBus,
        );

        $handler(new SyncMergeRequestsCommand($project->getId()->toRfc4122()));

        expect($mrRepo->saved)->toHaveCount(2);
        expect($mrRepo->saved[0]->getExternalId())->toBe('1');
        expect($mrRepo->saved[0]->getStatus())->toBe(MergeRequestStatus::Open);
        expect($mrRepo->saved[0]->getTitle())->toBe('MR Title');
        expect($mrRepo->saved[0]->getDescription())->toBe('Description');
        expect($mrRepo->saved[0]->getSourceBranch())->toBe('feature/test');
        expect($mrRepo->saved[0]->getTargetBranch())->toBe('main');
        expect($mrRepo->saved[0]->getAuthor())->toBe('dev');
        expect($mrRepo->saved[0]->getAdditions())->toBe(10);
        expect($mrRepo->saved[0]->getDeletions())->toBe(5);
        expect($mrRepo->saved[0]->getReviewers())->toBe(['alice']);
        expect($mrRepo->saved[0]->getLabels())->toBe(['feature']);
        expect($mrRepo->saved[1]->getExternalId())->toBe('2');
        expect($mrRepo->saved[1]->getStatus())->toBe(MergeRequestStatus::Draft);

        expect($eventBus->dispatched)->toHaveCount(1);
        expect($eventBus->dispatched[0])->toBeInstanceOf(MergeRequestsSyncedEvent::class);
        expect($eventBus->dispatched[0]->projectId)->toBe($project->getId()->toRfc4122());
        expect($eventBus->dispatched[0]->created)->toBe(2);
        expect($eventBus->dispatched[0]->updated)->toBe(0);
    });

    it('updates existing merge requests', function () {
        $provider = ProviderFactory::create();
        $project = \createSyncMRProject($provider);

        $existingMR = MergeRequest::create(
            externalId: '1',
            title: 'Old title',
            description: null,
            sourceBranch: 'feature/test',
            targetBranch: 'main',
            status: MergeRequestStatus::Open,
            author: 'dev',
            url: 'https://gitlab.example.com/test/project/-/merge_requests/1',
            additions: null,
            deletions: null,
            reviewers: [],
            labels: [],
            mergedAt: null,
            closedAt: null,
            project: $project,
        );

        $remoteMR = new RemoteMergeRequest(
            externalId: '1',
            title: 'Updated title',
            description: 'Now with description',
            sourceBranch: 'feature/test',
            targetBranch: 'main',
            status: 'merged',
            author: 'dev',
            url: 'https://gitlab.example.com/test/project/-/merge_requests/1',
            additions: 50,
            deletions: 10,
            reviewers: ['alice'],
            labels: ['done'],
            createdAt: '2026-03-10T10:00:00Z',
            updatedAt: '2026-03-12T10:00:00Z',
            mergedAt: '2026-03-12T10:00:00Z',
            closedAt: null,
        );

        $mrRepo = \stubSyncMRRepo($existingMR);
        $eventBus = \spySyncMREventBus();

        $handler = new SyncMergeRequestsHandler(
            \stubSyncMRProjectRepo($project),
            $mrRepo,
            \stubSyncMRGitFactory([$remoteMR]),
            $eventBus,
        );

        $handler(new SyncMergeRequestsCommand($project->getId()->toRfc4122()));

        expect($existingMR->getTitle())->toBe('Updated title');
        expect($existingMR->getDescription())->toBe('Now with description');
        expect($existingMR->getStatus())->toBe(MergeRequestStatus::Merged);
        expect($existingMR->getAdditions())->toBe(50);
        expect($existingMR->getDeletions())->toBe(10);
        expect($existingMR->getReviewers())->toBe(['alice']);
        expect($existingMR->getLabels())->toBe(['done']);

        expect($eventBus->dispatched[0]->created)->toBe(0);
        expect($eventBus->dispatched[0]->updated)->toBe(1);
    });

    it('dispatches ProjectSyncCompletedEvent when syncJobId is set', function () {
        $provider = ProviderFactory::create();
        $project = \createSyncMRProject($provider);
        $remoteMRs = [\makeRemoteMR('1')];

        $mrRepo = \stubSyncMRRepo();
        $eventBus = \spySyncMREventBus();

        $handler = new SyncMergeRequestsHandler(
            \stubSyncMRProjectRepo($project),
            $mrRepo,
            \stubSyncMRGitFactory($remoteMRs),
            $eventBus,
        );

        $syncJobId = Uuid::v7()->toRfc4122();
        $handler(new SyncMergeRequestsCommand($project->getId()->toRfc4122(), syncJobId: $syncJobId));

        expect($eventBus->dispatched)->toHaveCount(2);
        expect($eventBus->dispatched[0])->toBeInstanceOf(MergeRequestsSyncedEvent::class);
        expect($eventBus->dispatched[1])->toBeInstanceOf(\App\Catalog\Domain\Event\ProjectSyncCompletedEvent::class);
        expect($eventBus->dispatched[1]->syncJobId)->toBe($syncJobId);
    });

    it('ignores project without provider', function () {
        $project = Project::create(
            name: 'No Provider',
            slug: 'no-provider',
            description: null,
            repositoryUrl: 'https://local.git',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
        );

        $mrRepo = \stubSyncMRRepo();
        $eventBus = \spySyncMREventBus();

        $handler = new SyncMergeRequestsHandler(
            \stubSyncMRProjectRepo($project),
            $mrRepo,
            \stubSyncMRGitFactory(),
            $eventBus,
        );

        $handler(new SyncMergeRequestsCommand($project->getId()->toRfc4122()));

        expect($mrRepo->saved)->toBeEmpty();
        expect($eventBus->dispatched)->toBeEmpty();
    });

    it('ignores unknown project', function () {
        $mrRepo = \stubSyncMRRepo();
        $eventBus = \spySyncMREventBus();

        $handler = new SyncMergeRequestsHandler(
            \stubSyncMRProjectRepo(null),
            $mrRepo,
            \stubSyncMRGitFactory(),
            $eventBus,
        );

        $handler(new SyncMergeRequestsCommand(Uuid::v7()->toRfc4122()));

        expect($mrRepo->saved)->toBeEmpty();
        expect($eventBus->dispatched)->toBeEmpty();
    });
});

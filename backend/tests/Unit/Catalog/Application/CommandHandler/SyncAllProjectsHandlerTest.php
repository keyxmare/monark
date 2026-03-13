<?php

declare(strict_types=1);

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\Command\SyncAllProjectsCommand;
use App\Catalog\Application\Command\SyncMergeRequestsCommand;
use App\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Catalog\Application\CommandHandler\SyncAllProjectsHandler;
use App\Catalog\Application\DTO\SyncJobOutput;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function stubSyncProjectRepo(array $byProvider = [], array $allWithProvider = []): ProjectRepositoryInterface
{
    return new class ($byProvider, $allWithProvider) implements ProjectRepositoryInterface {
        public function __construct(
            private readonly array $byProvider,
            private readonly array $allWithProvider,
        ) {
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
            return [];
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByProviderId(Uuid $providerId): array
        {
            return $this->byProvider;
        }
        public function findAllWithProvider(): array
        {
            return $this->allWithProvider;
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

function stubSyncProviderRepo(?Provider $provider = null): ProviderRepositoryInterface
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

function stubSyncJobRepo(): SyncJobRepositoryInterface
{
    return new class () implements SyncJobRepositoryInterface {
        public ?SyncJob $saved = null;
        public function findById(Uuid $id): ?SyncJob
        {
            return null;
        }
        public function save(SyncJob $syncJob): void
        {
            $this->saved = $syncJob;
        }
    };
}

function spyCommandBus(): object
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

function createProjectWithProvider(Provider $provider, string $slug): Project
{
    return Project::create(
        name: 'Project ' . $slug,
        slug: $slug,
        description: null,
        repositoryUrl: 'https://gitlab.example.com/' . $slug,
        defaultBranch: 'main',
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
        provider: $provider,
        externalId: (string) \random_int(1, 9999),
    );
}

describe('SyncAllProjectsHandler', function () {
    it('dispatches scan commands for all projects of a provider', function () {
        $provider = ProviderFactory::create();
        $p1 = \createProjectWithProvider($provider, 'proj-a');
        $p2 = \createProjectWithProvider($provider, 'proj-b');
        $p3 = \createProjectWithProvider($provider, 'proj-c');

        $projectRepo = \stubSyncProjectRepo(byProvider: [$p1, $p2, $p3]);
        $providerRepo = \stubSyncProviderRepo($provider);
        $bus = \spyCommandBus();

        $handler = new SyncAllProjectsHandler($projectRepo, $providerRepo, \stubSyncJobRepo(), $bus);
        $result = $handler(new SyncAllProjectsCommand($provider->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(SyncJobOutput::class);
        expect($result->projectsCount)->toBe(3);
        expect($bus->dispatched)->toHaveCount(9);
        expect($bus->dispatched[0])->toBeInstanceOf(ScanProjectCommand::class);
        expect($bus->dispatched[0]->projectId)->toBe($p1->getId()->toRfc4122());
        expect($bus->dispatched[1])->toBeInstanceOf(SyncProjectMetadataCommand::class);
        expect($bus->dispatched[1]->projectId)->toBe($p1->getId()->toRfc4122());
        expect($bus->dispatched[2])->toBeInstanceOf(SyncMergeRequestsCommand::class);
        expect($bus->dispatched[2]->projectId)->toBe($p1->getId()->toRfc4122());
    });

    it('dispatches scan commands for all projects globally', function () {
        $provider = ProviderFactory::create();
        $p1 = \createProjectWithProvider($provider, 'proj-x');
        $p2 = \createProjectWithProvider($provider, 'proj-y');

        $projectRepo = \stubSyncProjectRepo(allWithProvider: [$p1, $p2]);
        $providerRepo = \stubSyncProviderRepo();
        $bus = \spyCommandBus();

        $handler = new SyncAllProjectsHandler($projectRepo, $providerRepo, \stubSyncJobRepo(), $bus);
        $result = $handler(new SyncAllProjectsCommand());

        expect($result->projectsCount)->toBe(2);
        expect($bus->dispatched)->toHaveCount(6);
    });

    it('returns zero when provider has no projects', function () {
        $provider = ProviderFactory::create();
        $projectRepo = \stubSyncProjectRepo(byProvider: []);
        $providerRepo = \stubSyncProviderRepo($provider);
        $bus = \spyCommandBus();

        $handler = new SyncAllProjectsHandler($projectRepo, $providerRepo, \stubSyncJobRepo(), $bus);
        $result = $handler(new SyncAllProjectsCommand($provider->getId()->toRfc4122()));

        expect($result->projectsCount)->toBe(0);
        expect($bus->dispatched)->toHaveCount(0);
    });

    it('throws not found for unknown provider', function () {
        $projectRepo = \stubSyncProjectRepo();
        $providerRepo = \stubSyncProviderRepo(null);
        $bus = \spyCommandBus();

        $handler = new SyncAllProjectsHandler($projectRepo, $providerRepo, \stubSyncJobRepo(), $bus);
        $handler(new SyncAllProjectsCommand(Uuid::v7()->toRfc4122()));
    })->throws(\DomainException::class);

    it('returns a valid startedAt timestamp', function () {
        $projectRepo = \stubSyncProjectRepo(allWithProvider: []);
        $providerRepo = \stubSyncProviderRepo();
        $bus = \spyCommandBus();

        $handler = new SyncAllProjectsHandler($projectRepo, $providerRepo, \stubSyncJobRepo(), $bus);
        $result = $handler(new SyncAllProjectsCommand());

        expect($result->startedAt)->not->toBeEmpty();
        $date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $result->startedAt);
        expect($date)->toBeInstanceOf(\DateTimeImmutable::class);
    });
});

<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Sync\Application\EventListener\GlobalSyncProgressListener;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function makeProgressListenerJobRepo(?GlobalSyncJob $job): GlobalSyncJobRepositoryInterface
{
    return new class ($job) implements GlobalSyncJobRepositoryInterface {
        public int $saveCount = 0;

        public function __construct(private ?GlobalSyncJob $job)
        {
        }

        public function save(GlobalSyncJob $job): void
        {
            ++$this->saveCount;
        }

        public function findById(Uuid $id): ?GlobalSyncJob
        {
            return $this->job;
        }

        public function findRunning(): ?GlobalSyncJob
        {
            return $this->job?->isRunning() ? $this->job : null;
        }

        public function incrementProgressAtomic(Uuid $jobId): array
        {
            if ($this->job !== null) {
                $this->job->incrementProgress();

                return ['progress' => $this->job->getStepProgress(), 'total' => $this->job->getStepTotal()];
            }

            return ['progress' => 0, 'total' => 0];
        }

        public function findByIdForUpdate(Uuid $id): ?GlobalSyncJob
        {
            return $this->job;
        }
    };
}

function makeProgressListenerProjectRepo(array $withProvider = []): ProjectRepositoryInterface
{
    return new class ($withProvider) implements ProjectRepositoryInterface {
        public function __construct(private readonly array $withProvider)
        {
        }

        public function findAllWithProvider(): array
        {
            return $this->withProvider;
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

function makeProgressListenerDepRepo(int $unique, int $byProject = 0): DependencyRepositoryInterface
{
    return new class ($unique, $byProject) implements DependencyRepositoryInterface {
        public function __construct(private readonly int $unique, private readonly int $byProject)
        {
        }

        public function findUniquePackages(): array
        {
            return \array_fill(0, $this->unique, ['name' => 'pkg', 'packageManager' => null]);
        }

        public function countByProjectId(Uuid $projectId): int
        {
            return $this->byProject;
        }

        public function findById(Uuid $id): ?Dependency
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

        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }

        public function save(Dependency $dependency): void
        {
        }

        public function delete(Dependency $dependency): void
        {
        }

        public function deleteByProjectId(Uuid $projectId): void
        {
        }

        public function findFiltered(int $page, int $perPage, array $filters = []): array
        {
            return [];
        }

        public function countFiltered(array $filters = []): int
        {
            return 0;
        }

        public function findByName(string $name, string $packageManager): array
        {
            return [];
        }

        public function findByNameManagerAndProjectId(string $name, string $packageManager, Uuid $projectId): ?Dependency
        {
            return null;
        }

        public function getStats(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }

        public function getStatsSingle(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }

        public function findFilteredWithVersionDates(int $page, int $perPage, array $filters = []): array
        {
            return [];
        }
    };
}

function makeProgressListenerProductRepo(int $count): ProductRepositoryInterface
{
    return new class ($count) implements ProductRepositoryInterface {
        public function __construct(private readonly int $count)
        {
        }

        public function findAll(): array
        {
            return \array_fill(0, $this->count, null);
        }

        public function findByNameAndManager(string $name, mixed $packageManager): ?Product
        {
            return null;
        }

        public function findStale(\DateTimeImmutable $before): array
        {
            return [];
        }

        public function findByNames(array $names): array
        {
            return [];
        }

        public function save(Product $product): void
        {
        }
    };
}

function makeProgressListenerBus(): MessageBusInterface
{
    return new class () implements MessageBusInterface {
        /** @var list<object> */
        public array $dispatched = [];

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched[] = $message;

            return new Envelope($message);
        }
    };
}

function makeProgressListenerHub(): HubInterface
{
    return new class () implements HubInterface {
        /** @var list<Update> */
        public array $published = [];

        public function getPublicUrl(): string
        {
            return 'http://localhost/.well-known/mercure';
        }

        public function getFactory(): ?TokenFactoryInterface
        {
            return null;
        }

        public function publish(Update $update): string
        {
            $this->published[] = $update;

            return '';
        }
    };
}

function makeProgressListenerEvent(): ProjectScannedEvent
{
    return new ProjectScannedEvent(
        projectId: Uuid::v7()->toRfc4122(),
        scanResult: new ScanResult([], []),
    );
}

describe('GlobalSyncProgressListener', function (): void {
    it('does nothing when no running job', function (): void {
        $hub = \makeProgressListenerHub();
        $listener = new GlobalSyncProgressListener(
            \makeProgressListenerJobRepo(null),
            \makeProgressListenerProjectRepo(),
            \makeProgressListenerDepRepo(0),
            \makeProgressListenerProductRepo(0),
            \makeProgressListenerBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeProgressListenerEvent());

        expect($hub->published)->toHaveCount(0);
    });

    it('does nothing when job is not on sync_projects step', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncCoverage, 3);
        $hub = \makeProgressListenerHub();

        $listener = new GlobalSyncProgressListener(
            \makeProgressListenerJobRepo($job),
            \makeProgressListenerProjectRepo(),
            \makeProgressListenerDepRepo(0),
            \makeProgressListenerProductRepo(0),
            \makeProgressListenerBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeProgressListenerEvent());

        expect($hub->published)->toHaveCount(0);
        expect($job->getStepProgress())->toBe(0);
    });

    it('increments progress on event and publishes a Mercure update', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncProjects, 5);
        $hub = \makeProgressListenerHub();

        $listener = new GlobalSyncProgressListener(
            \makeProgressListenerJobRepo($job),
            \makeProgressListenerProjectRepo(),
            \makeProgressListenerDepRepo(0),
            \makeProgressListenerProductRepo(0),
            \makeProgressListenerBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeProgressListenerEvent());

        expect($job->getStepProgress())->toBe(1);
        expect($hub->published)->toHaveCount(1);
        $payload = \json_decode($hub->published[0]->getData(), true);
        expect($payload['currentStepName'])->toBe('sync_projects');
        expect($payload['stepProgress'])->toBe(1);
    });

    it('skips to SyncVersions when last project scanned but no eligible projects', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncProjects, 1);
        $commandBus = \makeProgressListenerBus();
        $hub = \makeProgressListenerHub();

        $listener = new GlobalSyncProgressListener(
            \makeProgressListenerJobRepo($job),
            \makeProgressListenerProjectRepo([]),
            \makeProgressListenerDepRepo(3),
            \makeProgressListenerProductRepo(2),
            $commandBus,
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeProgressListenerEvent());

        expect($job->getCurrentStepName())->toBe(GlobalSyncStep::SyncVersions->name());
        expect($job->getStepTotal())->toBe(5);

        $dispatchedClasses = \array_map('get_class', $commandBus->dispatched);
        expect($dispatchedClasses)->toContain(SyncDependencyVersionsCommand::class);
        expect($dispatchedClasses)->toContain(SyncProductVersionsCommand::class);
    });
});

<?php

declare(strict_types=1);

use App\Hub\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Monitoring\Sync\Application\EventListener\GlobalSyncFrameworksProgressListener;
use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use App\Monitoring\Sync\Domain\Model\GlobalSyncStep;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function makeFrameworksListenerJobRepo(?GlobalSyncJob $job): GlobalSyncJobRepositoryInterface
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
            return $this->incrementProgressByAtomic($jobId, 1);
        }

        public function incrementProgressByAtomic(Uuid $jobId, int $delta): array
        {
            if ($this->job !== null) {
                for ($i = 0; $i < $delta; ++$i) {
                    $this->job->incrementProgress();
                }

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

function makeFrameworksListenerHub(): HubInterface
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

function makeFrameworksListenerProjectRepo(array $withProvider = []): ProjectRepositoryInterface
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

function makeFrameworksListenerDependencyRepo(array $countsByProject = []): DependencyRepositoryInterface
{
    return new class ($countsByProject) implements DependencyRepositoryInterface {
        public function __construct(private readonly array $countsByProject)
        {
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

        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }

        public function countByProjects(array $projectIds): array
        {
            return $this->countsByProject;
        }

        public function countVulnerabilitiesByProjects(array $projectIds): array
        {
            return [];
        }
        public function countVulnerabilitiesBySeverityForProjects(array $projectIds): array
        {
            return [];
        }

        public function countOutdatedByProjects(array $projectIds): array
        {
            return [];
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

        public function findUniquePackages(): array
        {
            return [];
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

function makeFrameworksListenerCommandBus(): MessageBusInterface
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

describe('GlobalSyncFrameworksProgressListener', function (): void {
    it('ignores ProductVersionsSyncedEvent when no running job', function (): void {
        $hub = \makeFrameworksListenerHub();
        $listener = new GlobalSyncFrameworksProgressListener(
            \makeFrameworksListenerJobRepo(null),
            \makeFrameworksListenerProjectRepo(),
            \makeFrameworksListenerDependencyRepo(),
            \makeFrameworksListenerCommandBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: null,
            ltsVersion: null,
        ));

        expect($hub->published)->toHaveCount(0);
    });

    it('ignores event when job is not on sync_frameworks step', function (): void {
        $job = GlobalSyncJob::create();
        $hub = \makeFrameworksListenerHub();

        $listener = new GlobalSyncFrameworksProgressListener(
            \makeFrameworksListenerJobRepo($job),
            \makeFrameworksListenerProjectRepo(),
            \makeFrameworksListenerDependencyRepo(),
            \makeFrameworksListenerCommandBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: null,
            ltsVersion: null,
        ));

        expect($hub->published)->toHaveCount(0);
    });

    it('increments progress and publishes message on ProductVersionsSyncedEvent', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncFrameworks, 5);
        $hub = \makeFrameworksListenerHub();

        $listener = new GlobalSyncFrameworksProgressListener(
            \makeFrameworksListenerJobRepo($job),
            \makeFrameworksListenerProjectRepo(),
            \makeFrameworksListenerDependencyRepo(),
            \makeFrameworksListenerCommandBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new ProductVersionsSyncedEvent(
            productName: 'react',
            packageManager: null,
            latestVersion: null,
            ltsVersion: null,
        ));

        expect($job->getStepProgress())->toBe(1);
        expect($hub->published)->toHaveCount(1);
        $payload = \json_decode($hub->published[0]->getData(), true);
        expect($payload['message'])->toBe('react');
        expect($payload['currentStepName'])->toBe('sync_frameworks');
    });

    it('transitions to ScanCve with 0 total and completes when no eligible projects', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncFrameworks, 1);
        $hub = \makeFrameworksListenerHub();
        $bus = \makeFrameworksListenerCommandBus();

        $listener = new GlobalSyncFrameworksProgressListener(
            \makeFrameworksListenerJobRepo($job),
            \makeFrameworksListenerProjectRepo(withProvider: []),
            \makeFrameworksListenerDependencyRepo(),
            $bus,
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new ProductVersionsSyncedEvent(
            productName: 'vue',
            packageManager: null,
            latestVersion: null,
            ltsVersion: null,
        ));

        expect($job->getCurrentStepName())->toBe(GlobalSyncStep::ScanCve->name());
        expect($job->isRunning())->toBeFalse();
        expect($bus->dispatched)->toHaveCount(0);
    });
});

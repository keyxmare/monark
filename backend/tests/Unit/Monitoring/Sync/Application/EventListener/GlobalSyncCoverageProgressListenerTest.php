<?php

declare(strict_types=1);

use App\Hub\Shared\Domain\Event\ProjectCoverageFetchedEvent;
use App\Monitoring\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Monitoring\Sync\Application\EventListener\GlobalSyncCoverageProgressListener;
use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use App\Monitoring\Sync\Domain\Model\GlobalSyncStep;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function makeCoverageJobRepo(?GlobalSyncJob $job): GlobalSyncJobRepositoryInterface
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

        public function incrementProgressAtomic(\Symfony\Component\Uid\Uuid $jobId): array
        {
            return $this->incrementProgressByAtomic($jobId, 1);
        }

        public function incrementProgressByAtomic(\Symfony\Component\Uid\Uuid $jobId, int $delta): array
        {
            if ($this->job !== null) {
                for ($i = 0; $i < $delta; ++$i) {
                    $this->job->incrementProgress();
                }

                return ['progress' => $this->job->getStepProgress(), 'total' => $this->job->getStepTotal()];
            }

            return ['progress' => 0, 'total' => 0];
        }

        public function findByIdForUpdate(\Symfony\Component\Uid\Uuid $jobId): ?\App\Monitoring\Sync\Domain\Model\GlobalSyncJob
        {
            return $this->job ?? null;
        }
    };
}

function makeCoverageDependencyRepo(int $count): DependencyRepositoryInterface
{
    return new class ($count) implements DependencyRepositoryInterface {
        public function __construct(private readonly int $count)
        {
        }

        public function findUniquePackages(): array
        {
            return \array_fill(0, $this->count, ['name' => 'pkg', 'packageManager' => null]);
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
            return $this->count;
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

        public function countByProjects(array $projectIds): array
        {
            return [];
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
    };
}

function makeCoverageCommandBus(): MessageBusInterface
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

function makeCoverageMercureHub(): HubInterface
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

function makeCoverageEvent(string $projectName = 'my-project', ?float $percent = 85.5): ProjectCoverageFetchedEvent
{
    return new ProjectCoverageFetchedEvent(
        projectId: Uuid::v7()->toRfc4122(),
        syncId: Uuid::v7()->toRfc4122(),
        projectName: $projectName,
        coveragePercent: $percent,
    );
}

describe('GlobalSyncCoverageProgressListener', function (): void {
    it('increments progress on ProjectCoverageFetchedEvent', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncCoverage, 5);

        $repo = \makeCoverageJobRepo($job);
        $hub = \makeCoverageMercureHub();

        $listener = new GlobalSyncCoverageProgressListener(
            $repo,
            \makeCoverageDependencyRepo(0),
            \makeCoverageCommandBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeCoverageEvent('my-project', 85.5));

        expect($job->getStepProgress())->toBe(1);
        expect($hub->published)->toHaveCount(1);

        $payload = \json_decode($hub->published[0]->getData(), true);
        expect($payload['message'])->toBe('my-project: 85.5%');
        expect($payload['currentStepName'])->toBe('sync_coverage');
    });

    it('formats null coverage as n/a in message', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncCoverage, 5);

        $hub = \makeCoverageMercureHub();

        $listener = new GlobalSyncCoverageProgressListener(
            \makeCoverageJobRepo($job),
            \makeCoverageDependencyRepo(0),
            \makeCoverageCommandBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeCoverageEvent('no-cov-project', null));

        $payload = \json_decode($hub->published[0]->getData(), true);
        expect($payload['message'])->toBe('no-cov-project: n/a');
    });

    it('transitions to SyncDependencies when all coverage fetched', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncCoverage, 1);

        $commandBus = \makeCoverageCommandBus();
        $hub = \makeCoverageMercureHub();

        $listener = new GlobalSyncCoverageProgressListener(
            \makeCoverageJobRepo($job),
            \makeCoverageDependencyRepo(3),
            $commandBus,
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeCoverageEvent());

        expect($job->getCurrentStepName())->toBe(GlobalSyncStep::SyncDependencies->name());
        expect($job->getStepTotal())->toBe(3);
        expect($commandBus->dispatched)->toHaveCount(1);
        expect($commandBus->dispatched[0])->toBeInstanceOf(SyncDependencyVersionsCommand::class);
        expect($hub->published)->toHaveCount(2);
    });

    it('ignores event when job is not on sync_coverage step', function (): void {
        $job = GlobalSyncJob::create();

        $commandBus = \makeCoverageCommandBus();
        $hub = \makeCoverageMercureHub();

        $listener = new GlobalSyncCoverageProgressListener(
            \makeCoverageJobRepo($job),
            \makeCoverageDependencyRepo(0),
            $commandBus,
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeCoverageEvent());

        expect($job->getStepProgress())->toBe(0);
        expect($hub->published)->toHaveCount(0);
    });

    it('ignores event when no running job', function (): void {
        $commandBus = \makeCoverageCommandBus();
        $hub = \makeCoverageMercureHub();

        $listener = new GlobalSyncCoverageProgressListener(
            \makeCoverageJobRepo(null),
            \makeCoverageDependencyRepo(0),
            $commandBus,
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(\makeCoverageEvent());

        expect($hub->published)->toHaveCount(0);
        expect($commandBus->dispatched)->toHaveCount(0);
    });
});

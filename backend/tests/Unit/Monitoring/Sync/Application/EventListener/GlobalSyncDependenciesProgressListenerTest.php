<?php

declare(strict_types=1);

use App\Monitoring\Dependency\Domain\Event\DependencyVersionSynced;
use App\Monitoring\Sync\Application\EventListener\GlobalSyncDependenciesProgressListener;
use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use App\Monitoring\Sync\Domain\Model\GlobalSyncStep;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\Monitoring\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\Monitoring\VersionRegistry\Domain\Model\Product;
use App\Monitoring\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function makeDependenciesListenerJobRepo(?GlobalSyncJob $job): GlobalSyncJobRepositoryInterface
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

function makeDependenciesListenerProductRepo(int $count): ProductRepositoryInterface
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

        public function findByResolverSource(\App\Monitoring\VersionRegistry\Domain\Model\ResolverSource $source): array
        {
            return \array_fill(0, $this->count, null);
        }
    };
}

function makeDependenciesListenerBus(): MessageBusInterface
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

function makeDependenciesListenerHub(): HubInterface
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

describe('GlobalSyncDependenciesProgressListener', function (): void {
    it('ignores DependencyVersionSynced when no running job', function (): void {
        $hub = \makeDependenciesListenerHub();
        $bus = \makeDependenciesListenerBus();

        $listener = new GlobalSyncDependenciesProgressListener(
            \makeDependenciesListenerJobRepo(null),
            \makeDependenciesListenerProductRepo(0),
            $bus,
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyVersionSynced(packageName: 'lodash', packageManager: 'npm'));

        expect($hub->published)->toHaveCount(0);
        expect($bus->dispatched)->toHaveCount(0);
    });

    it('ignores event when job is not on sync_dependencies step', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncCoverage, 3);
        $hub = \makeDependenciesListenerHub();
        $bus = \makeDependenciesListenerBus();

        $listener = new GlobalSyncDependenciesProgressListener(
            \makeDependenciesListenerJobRepo($job),
            \makeDependenciesListenerProductRepo(0),
            $bus,
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyVersionSynced(packageName: 'lodash', packageManager: 'npm'));

        expect($hub->published)->toHaveCount(0);
        expect($bus->dispatched)->toHaveCount(0);
        expect($job->getStepProgress())->toBe(0);
    });

    it('increments progress and publishes a Mercure update on DependencyVersionSynced', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncDependencies, 5);
        $hub = \makeDependenciesListenerHub();

        $listener = new GlobalSyncDependenciesProgressListener(
            \makeDependenciesListenerJobRepo($job),
            \makeDependenciesListenerProductRepo(0),
            \makeDependenciesListenerBus(),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyVersionSynced(packageName: 'lodash', packageManager: 'npm'));

        expect($job->getStepProgress())->toBe(1);
        expect($hub->published)->toHaveCount(1);
        $payload = \json_decode($hub->published[0]->getData(), true);
        expect($payload['message'])->toBe('lodash');
        expect($payload['currentStepName'])->toBe('sync_dependencies');
    });

    it('transitions to SyncFrameworks and dispatches SyncProductVersionsCommand when all dependencies synced', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncDependencies, 1);
        $hub = \makeDependenciesListenerHub();
        $bus = \makeDependenciesListenerBus();

        $listener = new GlobalSyncDependenciesProgressListener(
            \makeDependenciesListenerJobRepo($job),
            \makeDependenciesListenerProductRepo(2),
            $bus,
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyVersionSynced(packageName: 'vue', packageManager: 'npm'));

        expect($job->getCurrentStepName())->toBe(GlobalSyncStep::SyncFrameworks->name());
        expect($job->getStepTotal())->toBe(2);

        $dispatchedClasses = \array_map('get_class', $bus->dispatched);
        expect($dispatchedClasses)->toContain(SyncProductVersionsCommand::class);
        expect($hub->published)->toHaveCount(2);
    });
});

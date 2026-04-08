<?php

declare(strict_types=1);

use App\Dependency\Domain\Event\DependencyVersionSynced;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Sync\Application\EventListener\GlobalSyncVersionProgressListener;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;

function makeVersionListenerJobRepo(?GlobalSyncJob $job): GlobalSyncJobRepositoryInterface
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

function makeVersionListenerHub(): HubInterface
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

describe('GlobalSyncVersionProgressListener', function (): void {
    it('ignores ProductVersionsSyncedEvent when no running job', function (): void {
        $hub = \makeVersionListenerHub();
        $listener = new GlobalSyncVersionProgressListener(
            \makeVersionListenerJobRepo(null),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        $listener->onProductSynced(new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: null,
            ltsVersion: null,
        ));

        expect($hub->published)->toHaveCount(0);
    });

    it('ignores event when job is not on sync_versions step', function (): void {
        $job = GlobalSyncJob::create();
        $hub = \makeVersionListenerHub();

        $listener = new GlobalSyncVersionProgressListener(
            \makeVersionListenerJobRepo($job),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        $listener->onProductSynced(new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: null,
            ltsVersion: null,
        ));

        expect($hub->published)->toHaveCount(0);
    });

    it('increments progress and publishes message on onProductSynced', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncVersions, 5);
        $hub = \makeVersionListenerHub();

        $listener = new GlobalSyncVersionProgressListener(
            \makeVersionListenerJobRepo($job),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        $listener->onProductSynced(new ProductVersionsSyncedEvent(
            productName: 'react',
            packageManager: null,
            latestVersion: null,
            ltsVersion: null,
        ));

        expect($job->getStepProgress())->toBe(1);
        expect($hub->published)->toHaveCount(1);
        $payload = \json_decode($hub->published[0]->getData(), true);
        expect($payload['message'])->toBe('react');
        expect($payload['currentStepName'])->toBe('sync_versions');
    });

    it('increments progress on onDependencySynced with package name as message', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncVersions, 3);
        $hub = \makeVersionListenerHub();

        $listener = new GlobalSyncVersionProgressListener(
            \makeVersionListenerJobRepo($job),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        $listener->onDependencySynced(new DependencyVersionSynced(
            packageName: 'lodash',
            packageManager: 'npm',
        ));

        expect($job->getStepProgress())->toBe(1);
        $payload = \json_decode($hub->published[0]->getData(), true);
        expect($payload['message'])->toBe('lodash');
    });

    it('transitions to ScanCve and completes job when all versions synced', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncVersions, 1);
        $hub = \makeVersionListenerHub();

        $listener = new GlobalSyncVersionProgressListener(
            \makeVersionListenerJobRepo($job),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        $listener->onProductSynced(new ProductVersionsSyncedEvent(
            productName: 'vue',
            packageManager: null,
            latestVersion: null,
            ltsVersion: null,
        ));

        expect($job->getCurrentStepName())->toBe(GlobalSyncStep::ScanCve->name());
        expect($job->isRunning())->toBeFalse();
        expect($hub->published)->toHaveCount(2);
    });
});

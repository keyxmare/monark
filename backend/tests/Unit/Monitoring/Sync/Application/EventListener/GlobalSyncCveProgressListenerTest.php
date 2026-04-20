<?php

declare(strict_types=1);

use App\Monitoring\Dependency\Domain\Event\DependencyCveSyncedEvent;
use App\Monitoring\Sync\Application\EventListener\GlobalSyncCveProgressListener;
use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use App\Monitoring\Sync\Domain\Model\GlobalSyncStep;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;

function makeCveListenerJobRepo(?GlobalSyncJob $job): GlobalSyncJobRepositoryInterface
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

function makeCveListenerHub(): HubInterface
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

describe('GlobalSyncCveProgressListener', function (): void {
    it('ignores DependencyCveSyncedEvent when no running job', function (): void {
        $hub = \makeCveListenerHub();
        $listener = new GlobalSyncCveProgressListener(
            \makeCveListenerJobRepo(null),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyCveSyncedEvent('project-1', 0, 3));

        expect($hub->published)->toHaveCount(0);
    });

    it('ignores event when job is not on scan_cve step', function (): void {
        $job = GlobalSyncJob::create();
        $hub = \makeCveListenerHub();

        $listener = new GlobalSyncCveProgressListener(
            \makeCveListenerJobRepo($job),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyCveSyncedEvent('project-1', 0, 3));

        expect($hub->published)->toHaveCount(0);
    });

    it('ignores events with zero dependencies scanned', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::ScanCve, 10);
        $hub = \makeCveListenerHub();

        $listener = new GlobalSyncCveProgressListener(
            \makeCveListenerJobRepo($job),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyCveSyncedEvent('project-1', 0, 3));

        expect($job->getStepProgress())->toBe(0);
        expect($hub->published)->toHaveCount(0);
    });

    it('increments progress by dependenciesScanned and publishes CVE count', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::ScanCve, 20);
        $hub = \makeCveListenerHub();

        $listener = new GlobalSyncCveProgressListener(
            \makeCveListenerJobRepo($job),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyCveSyncedEvent('project-1', 8, 7));

        expect($job->getStepProgress())->toBe(8);
        expect($hub->published)->toHaveCount(1);
        $payload = \json_decode($hub->published[0]->getData(), true);
        expect($payload['message'])->toBe('7 CVE');
        expect($payload['currentStepName'])->toBe('scan_cve');
    });

    it('completes job when all dependencies scanned', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::ScanCve, 5);
        $hub = \makeCveListenerHub();

        $listener = new GlobalSyncCveProgressListener(
            \makeCveListenerJobRepo($job),
            $hub,
            new \Psr\Log\NullLogger(),
        );

        ($listener)(new DependencyCveSyncedEvent('project-1', 5, 2));

        expect($job->isRunning())->toBeFalse();
        expect($hub->published)->toHaveCount(2);
    });
});

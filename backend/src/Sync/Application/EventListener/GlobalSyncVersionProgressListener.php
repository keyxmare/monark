<?php

declare(strict_types=1);

namespace App\Sync\Application\EventListener;

use App\Dependency\Domain\Event\DependencyVersionSynced;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

final readonly class GlobalSyncVersionProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onProductSynced(ProductVersionsSyncedEvent $event): void
    {
        $this->incrementSyncVersions($event->productName);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onDependencySynced(DependencyVersionSynced $event): void
    {
        $this->incrementSyncVersions($event->packageName);
    }

    private function incrementSyncVersions(?string $message): void
    {
        $job = $this->repository->findRunning();
        if ($job === null) {
            return;
        }

        if ($job->getCurrentStepName() !== GlobalSyncStep::SyncVersions->name()) {
            return;
        }

        $result = $this->repository->incrementProgressAtomic($job->getId());
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, $result['progress'], $result['total'], $message);

        if ($result['total'] > 0 && $result['progress'] === $result['total']) {
            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::SyncVersions->name()) {
                $this->transitionToScanCve($job);
            }
        }
    }

    private function transitionToScanCve(GlobalSyncJob $job): void
    {
        $job->startStep(GlobalSyncStep::ScanCve, 0);
        $job->complete();
        $this->repository->save($job);
        $this->publishProgress($job, null);
    }

    private function publishProgress(GlobalSyncJob $job, ?string $message): void
    {
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, $job->getStepProgress(), $job->getStepTotal(), $message);
    }

    private function publishProgressFromValues(string $syncId, GlobalSyncJob $job, int $progress, int $total, ?string $message): void
    {
        try {
            $this->mercureHub->publish(new Update(
                \sprintf('/global-sync/%s', $syncId),
                (string) \json_encode([
                    'syncId' => $syncId,
                    'status' => $job->getStatus()->value,
                    'currentStep' => $job->getCurrentStep(),
                    'currentStepName' => $job->getCurrentStepName(),
                    'stepProgress' => $progress,
                    'stepTotal' => $total,
                    'completedSteps' => $job->getCompletedStepNames(),
                    'message' => $message,
                ]),
            ));
        } catch (Throwable $e) {
            $this->logger->warning('Failed to publish sync progress to Mercure', [
                'syncId' => $syncId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

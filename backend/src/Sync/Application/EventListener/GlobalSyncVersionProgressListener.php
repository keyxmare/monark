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

        $job->incrementProgress();
        $this->repository->save($job);
        $this->publishProgress($job, $message);

        if ($job->getStepTotal() > 0 && $job->getStepProgress() >= $job->getStepTotal()) {
            $this->transitionToScanCve($job);
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
        $syncId = $job->getId()->toRfc4122();

        try {
            $this->mercureHub->publish(new Update(
                \sprintf('/global-sync/%s', $syncId),
                (string) \json_encode([
                    'syncId' => $syncId,
                    'status' => $job->getStatus()->value,
                    'currentStep' => $job->getCurrentStep(),
                    'currentStepName' => $job->getCurrentStepName(),
                    'stepProgress' => $job->getStepProgress(),
                    'stepTotal' => $job->getStepTotal(),
                    'completedSteps' => $job->getCompletedStepNames(),
                    'message' => $message,
                ]),
            ));
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to publish sync progress to Mercure', [
                'syncId' => $syncId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

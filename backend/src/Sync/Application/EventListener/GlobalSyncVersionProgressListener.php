<?php

declare(strict_types=1);

namespace App\Sync\Application\EventListener;

use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class GlobalSyncVersionProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private HubInterface $mercureHub,
    ) {
    }

    public function __invoke(ProductVersionsSyncedEvent $event): void
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
        $this->publishProgress($job, $event->productName);

        if ($job->getStepTotal() > 0 && $job->getStepProgress() >= $job->getStepTotal()) {
            $this->transitionToStep3($job);
        }
    }

    private function transitionToStep3(GlobalSyncJob $job): void
    {
        $job->startStep(GlobalSyncStep::ScanCve, 0);
        $job->complete();
        $this->repository->save($job);
        $this->publishProgress($job, null);
    }

    private function publishProgress(GlobalSyncJob $job, ?string $message): void
    {
        $syncId = $job->getId()->toRfc4122();

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
    }
}

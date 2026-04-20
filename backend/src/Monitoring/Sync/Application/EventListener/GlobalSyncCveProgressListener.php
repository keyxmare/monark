<?php

declare(strict_types=1);

namespace App\Monitoring\Sync\Application\EventListener;

use App\Monitoring\Dependency\Domain\Event\DependencyCveSyncedEvent;
use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use App\Monitoring\Sync\Domain\Model\GlobalSyncStep;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class GlobalSyncCveProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DependencyCveSyncedEvent $event): void
    {
        $job = $this->repository->findRunning();
        if ($job === null) {
            return;
        }

        if ($job->getCurrentStepName() !== GlobalSyncStep::ScanCve->name()) {
            return;
        }

        $delta = $event->dependenciesScanned;
        if ($delta <= 0) {
            return;
        }
        $result = $this->repository->incrementProgressByAtomic($job->getId(), $delta);
        $this->publishProgressFromValues(
            $job->getId()->toRfc4122(),
            $job,
            $result['progress'],
            $result['total'],
            \sprintf('%d CVE', $event->vulnerabilitiesFound),
        );

        if ($result['total'] > 0 && $result['progress'] >= $result['total']) {
            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::ScanCve->name()) {
                $job->complete();
                $this->repository->save($job);
                $this->publishProgressFromValues(
                    $job->getId()->toRfc4122(),
                    $job,
                    $job->getStepProgress(),
                    $job->getStepTotal(),
                    null,
                );
            }
        }
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

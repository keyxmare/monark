<?php

declare(strict_types=1);

namespace App\Sync\Application\EventListener;

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Event\ProjectCoverageFetchedEvent;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class GlobalSyncCoverageProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private DependencyRepositoryInterface $dependencyRepository,
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProjectCoverageFetchedEvent $event): void
    {
        $job = $this->repository->findRunning();
        if ($job === null) {
            return;
        }

        if ($job->getCurrentStepName() !== GlobalSyncStep::SyncCoverage->name()) {
            return;
        }

        $result = $this->repository->incrementProgressAtomic($job->getId());

        $message = $event->coveragePercent !== null
            ? \sprintf('%s: %.1f%%', $event->projectName, $event->coveragePercent)
            : \sprintf('%s: n/a', $event->projectName);
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, $result['progress'], $result['total'], $message);

        if ($result['progress'] === $result['total']) {
            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::SyncCoverage->name()) {
                $this->transitionToSyncVersions($job);
            }
        }
    }

    private function transitionToSyncVersions(GlobalSyncJob $job): void
    {
        $totalDeps = $job->getProjectId() !== null
            ? $this->dependencyRepository->countByProjectId(Uuid::fromString($job->getProjectId()))
            : \count($this->dependencyRepository->findUniquePackages());
        $totalProducts = \count($this->productRepository->findAll());
        $job->startStep(GlobalSyncStep::SyncVersions, $totalDeps + $totalProducts);
        $this->repository->save($job);
        $this->publishProgress($job, null);

        $syncId = $job->getId()->toRfc4122();
        $this->commandBus->dispatch(new SyncDependencyVersionsCommand(syncId: $syncId));
        $this->commandBus->dispatch(new SyncProductVersionsCommand(syncId: $syncId));
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

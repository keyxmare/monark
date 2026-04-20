<?php

declare(strict_types=1);

namespace App\Monitoring\Sync\Application\EventListener;

use App\Hub\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Dependency\Application\Command\SyncDependencyCveCommand;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use App\Monitoring\Sync\Domain\Model\GlobalSyncStep;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class GlobalSyncFrameworksProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private ProjectRepositoryInterface $projectRepository,
        private DependencyRepositoryInterface $dependencyRepository,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProductVersionsSyncedEvent $event): void
    {
        $job = $this->repository->findRunning();
        if ($job === null) {
            return;
        }

        if ($job->getCurrentStepName() !== GlobalSyncStep::SyncFrameworks->name()) {
            return;
        }

        $result = $this->repository->incrementProgressAtomic($job->getId());
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, $result['progress'], $result['total'], $event->productName);

        if ($result['total'] > 0 && $result['progress'] === $result['total']) {
            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::SyncFrameworks->name()) {
                $this->transitionToScanCve($job);
            }
        }
    }

    private function transitionToScanCve(GlobalSyncJob $job): void
    {
        if ($job->getProjectId() !== null) {
            $singleProject = $this->projectRepository->findById(Uuid::fromString($job->getProjectId()));
            $eligibleProjects = $singleProject !== null && $singleProject->getProvider() !== null ? [$singleProject] : [];
        } else {
            $eligibleProjects = $this->projectRepository->findAllWithProvider();
        }

        if (\count($eligibleProjects) === 0) {
            $job->startStep(GlobalSyncStep::ScanCve, 0);
            $this->repository->save($job);
            $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, 0, 0, null);

            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::ScanCve->name()) {
                $job->complete();
                $this->repository->save($job);
                $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, 0, 0, null);
            }

            return;
        }

        $projectIds = \array_map(static fn ($p) => $p->getId(), $eligibleProjects);
        $countsByProject = $this->dependencyRepository->countByProjects($projectIds);
        $totalDeps = \array_sum($countsByProject);

        $job->startStep(GlobalSyncStep::ScanCve, $totalDeps);
        $this->repository->save($job);
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, 0, $totalDeps, null);

        if ($totalDeps === 0) {
            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::ScanCve->name()) {
                $job->complete();
                $this->repository->save($job);
                $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, 0, 0, null);
            }

            return;
        }

        foreach ($eligibleProjects as $project) {
            $this->commandBus->dispatch(new SyncDependencyCveCommand(
                projectId: $project->getId()->toRfc4122(),
            ));
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

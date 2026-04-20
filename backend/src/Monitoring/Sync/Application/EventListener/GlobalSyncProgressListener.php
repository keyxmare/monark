<?php

declare(strict_types=1);

namespace App\Monitoring\Sync\Application\EventListener;

use App\Hub\Shared\Domain\Event\ProjectScannedEvent;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Coverage\Application\Command\FetchProjectCoverageCommand;
use App\Monitoring\Dependency\Application\Command\SyncDependencyVersionsCommand;
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
final readonly class GlobalSyncProgressListener
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

    public function __invoke(ProjectScannedEvent $event): void
    {
        $job = $this->repository->findRunning();
        if ($job === null) {
            return;
        }

        if ($job->getCurrentStepName() !== GlobalSyncStep::SyncProjects->name()) {
            return;
        }

        $result = $this->repository->incrementProgressAtomic($job->getId());
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, $result['progress'], $result['total']);

        if ($result['progress'] === $result['total']) {
            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::SyncProjects->name()) {
                $this->transitionToSyncCoverage($job);
            }
        }
    }

    private function transitionToSyncCoverage(GlobalSyncJob $job): void
    {
        if ($job->getProjectId() !== null) {
            $singleProject = $this->projectRepository->findById(Uuid::fromString($job->getProjectId()));
            $eligibleProjects = $singleProject !== null && $singleProject->getProvider() !== null ? [$singleProject] : [];
        } else {
            $eligibleProjects = $this->projectRepository->findAllWithProvider();
        }

        if (\count($eligibleProjects) === 0) {
            $this->skipToSyncDependencies($job);

            return;
        }

        $job->startStep(GlobalSyncStep::SyncCoverage, \count($eligibleProjects));
        $this->repository->save($job);
        $this->publishProgress($job);

        $syncId = $job->getId()->toRfc4122();
        foreach ($eligibleProjects as $project) {
            $this->commandBus->dispatch(new FetchProjectCoverageCommand(
                projectId: $project->getId()->toRfc4122(),
                syncId: $syncId,
            ));
        }
    }

    private function skipToSyncDependencies(GlobalSyncJob $job): void
    {
        $totalDeps = $job->getProjectId() !== null
            ? $this->dependencyRepository->countByProjectId(Uuid::fromString($job->getProjectId()))
            : \count($this->dependencyRepository->findUniquePackages());
        $job->startStep(GlobalSyncStep::SyncDependencies, $totalDeps);
        $this->repository->save($job);
        $this->publishProgress($job);

        $this->commandBus->dispatch(new SyncDependencyVersionsCommand(syncId: $job->getId()->toRfc4122()));
    }

    private function publishProgress(GlobalSyncJob $job): void
    {
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, $job->getStepProgress(), $job->getStepTotal());
    }

    private function publishProgressFromValues(string $syncId, GlobalSyncJob $job, int $progress, int $total): void
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

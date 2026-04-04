<?php

declare(strict_types=1);

namespace App\Sync\Application\EventListener;

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Application\Command\FetchProjectCoverageCommand;
use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Event\ProjectScannedEvent;
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

#[AsMessageHandler(bus: 'event.bus')]
final readonly class GlobalSyncProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private ProjectRepositoryInterface $projectRepository,
        private DependencyRepositoryInterface $dependencyRepository,
        private ProductRepositoryInterface $productRepository,
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

        $job->incrementProgress();
        $this->repository->save($job);
        $this->publishProgress($job);

        if ($job->getStepProgress() >= $job->getStepTotal()) {
            $this->transitionToSyncCoverage($job);
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
            $this->skipToSyncVersions($job);

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

    private function skipToSyncVersions(GlobalSyncJob $job): void
    {
        $totalDeps = $job->getProjectId() !== null
            ? $this->dependencyRepository->countByProjectId(Uuid::fromString($job->getProjectId()))
            : \count($this->dependencyRepository->findUniquePackages());
        $totalProducts = \count($this->productRepository->findAll());
        $job->startStep(GlobalSyncStep::SyncVersions, $totalDeps + $totalProducts);
        $this->repository->save($job);
        $this->publishProgress($job);

        $syncId = $job->getId()->toRfc4122();
        $this->commandBus->dispatch(new SyncDependencyVersionsCommand(syncId: $syncId));
        $this->commandBus->dispatch(new SyncProductVersionsCommand(syncId: $syncId));
    }

    private function publishProgress(GlobalSyncJob $job): void
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

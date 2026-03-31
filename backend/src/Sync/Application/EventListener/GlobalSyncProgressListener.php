<?php

declare(strict_types=1);

namespace App\Sync\Application\EventListener;

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class GlobalSyncProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private DependencyRepositoryInterface $dependencyRepository,
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
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
            $this->transitionToStep2($job);
        }
    }

    private function transitionToStep2(GlobalSyncJob $job): void
    {
        $totalDeps = \count($this->dependencyRepository->findUniquePackages());
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
    }
}

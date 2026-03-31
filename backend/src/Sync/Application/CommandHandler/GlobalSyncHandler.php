<?php

declare(strict_types=1);

namespace App\Sync\Application\CommandHandler;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\Command\SyncMergeRequestsCommand;
use App\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Sync\Application\Command\GlobalSyncCommand;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GlobalSyncHandler
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $globalSyncJobRepository,
        private ProjectRepositoryInterface $projectRepository,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
    ) {
    }

    public function __invoke(GlobalSyncCommand $command): void
    {
        $job = $this->globalSyncJobRepository->findById(Uuid::fromString($command->syncId));
        if ($job === null) {
            return;
        }

        try {
            $this->runStep1($command->syncId);
            $this->runStep2($command->syncId);
            $this->runStep3($command->syncId);

            $completed = $this->globalSyncJobRepository->findById(Uuid::fromString($command->syncId));
            if ($completed === null) {
                return;
            }

            $completed->complete();
            $this->globalSyncJobRepository->save($completed);
            $this->publishMercure($command->syncId, $completed);
        } catch (Throwable $e) {
            $failed = $this->globalSyncJobRepository->findById(Uuid::fromString($command->syncId));
            if ($failed !== null) {
                $failed->markFailed();
                $this->globalSyncJobRepository->save($failed);
            }

            throw $e;
        }
    }

    private function runStep1(string $syncId): void
    {
        $projects = $this->projectRepository->findAllWithProvider();
        $total = \count($projects);

        $job = $this->requireJob($syncId);
        $job->startStep(GlobalSyncStep::SyncProjects, $total);
        $this->globalSyncJobRepository->save($job);
        $this->publishMercure($syncId, $job);

        foreach ($projects as $project) {
            $projectId = $project->getId()->toRfc4122();

            $this->commandBus->dispatch(
                new ScanProjectCommand($projectId),
                [new DispatchAfterCurrentBusStamp()],
            );
            $this->commandBus->dispatch(
                new SyncProjectMetadataCommand($projectId),
                [new DispatchAfterCurrentBusStamp()],
            );
            $this->commandBus->dispatch(
                new SyncMergeRequestsCommand($projectId, false, $syncId),
                [new DispatchAfterCurrentBusStamp()],
            );

            $refreshed = $this->requireJob($syncId);
            $refreshed->incrementProgress();
            $this->globalSyncJobRepository->save($refreshed);
            $this->publishMercure($syncId, $refreshed);
        }
    }

    private function runStep2(string $syncId): void
    {
        $job = $this->requireJob($syncId);
        $job->startStep(GlobalSyncStep::SyncVersions, 0);
        $this->globalSyncJobRepository->save($job);
        $this->publishMercure($syncId, $job);

        $this->commandBus->dispatch(
            new SyncDependencyVersionsCommand(syncId: $syncId),
            [new DispatchAfterCurrentBusStamp()],
        );
        $this->commandBus->dispatch(
            new SyncProductVersionsCommand(syncId: $syncId),
            [new DispatchAfterCurrentBusStamp()],
        );
    }

    private function runStep3(string $syncId): void
    {
        $job = $this->requireJob($syncId);
        $job->startStep(GlobalSyncStep::ScanCve, 0);
        $this->globalSyncJobRepository->save($job);
        $this->publishMercure($syncId, $job);
    }

    private function requireJob(string $syncId): GlobalSyncJob
    {
        $job = $this->globalSyncJobRepository->findById(Uuid::fromString($syncId));
        if ($job === null) {
            throw new RuntimeException(\sprintf('GlobalSyncJob %s not found', $syncId));
        }

        return $job;
    }

    private function publishMercure(string $syncId, GlobalSyncJob $job): void
    {
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

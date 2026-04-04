<?php

declare(strict_types=1);

namespace App\Sync\Application\CommandHandler;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Sync\Application\Command\GlobalSyncCommand;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GlobalSyncHandler
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $globalSyncJobRepository,
        private ProjectRepositoryInterface $projectRepository,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GlobalSyncCommand $command): void
    {
        $job = $this->globalSyncJobRepository->findById(Uuid::fromString($command->syncId));
        if ($job === null) {
            return;
        }

        try {
            if ($command->projectId !== null) {
                $singleProject = $this->projectRepository->findById(Uuid::fromString($command->projectId));
                if ($singleProject === null || $singleProject->getProvider() === null) {
                    $job->markFailed();
                    $this->globalSyncJobRepository->save($job);
                    $this->publishProgress($command->syncId, $job);

                    return;
                }
                $projects = [$singleProject];
            } else {
                $projects = $this->projectRepository->findAllWithProvider();
            }
            $total = \count($projects);

            if ($total === 0) {
                $job->startStep(GlobalSyncStep::ScanCve, 0);
                $job->complete();
                $this->globalSyncJobRepository->save($job);
                $this->publishProgress($command->syncId, $job);

                return;
            }

            $job->startStep(GlobalSyncStep::SyncProjects, $total);
            $this->globalSyncJobRepository->save($job);
            $this->publishProgress($command->syncId, $job);

            foreach ($projects as $project) {
                $projectId = $project->getId()->toRfc4122();

                $this->commandBus->dispatch(new ScanProjectCommand($projectId));
                $this->commandBus->dispatch(new SyncProjectMetadataCommand($projectId));
            }
        } catch (\Throwable $e) {
            $job->markFailed();
            $this->globalSyncJobRepository->save($job);
            $this->publishProgress($command->syncId, $job);

            throw $e;
        }
    }

    private function publishProgress(string $syncId, \App\Sync\Domain\Model\GlobalSyncJob $job): void
    {
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

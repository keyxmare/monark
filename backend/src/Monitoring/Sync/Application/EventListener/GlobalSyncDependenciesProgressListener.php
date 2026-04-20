<?php

declare(strict_types=1);

namespace App\Monitoring\Sync\Application\EventListener;

use App\Monitoring\Dependency\Domain\Event\DependencyVersionSynced;
use App\Monitoring\Sync\Domain\Model\GlobalSyncJob;
use App\Monitoring\Sync\Domain\Model\GlobalSyncStep;
use App\Monitoring\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\Monitoring\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\Monitoring\VersionRegistry\Domain\Model\ResolverSource;
use App\Monitoring\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class GlobalSyncDependenciesProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DependencyVersionSynced $event): void
    {
        $job = $this->repository->findRunning();
        if ($job === null) {
            return;
        }

        if ($job->getCurrentStepName() !== GlobalSyncStep::SyncDependencies->name()) {
            return;
        }

        $result = $this->repository->incrementProgressAtomic($job->getId());
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, $result['progress'], $result['total'], $event->packageName);

        if ($result['total'] > 0 && $result['progress'] === $result['total']) {
            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::SyncDependencies->name()) {
                $this->transitionToSyncFrameworks($job);
            }
        } elseif ($result['total'] === 0) {
            $job = $this->repository->findByIdForUpdate($job->getId());
            if ($job !== null && $job->getCurrentStepName() === GlobalSyncStep::SyncDependencies->name()) {
                $this->transitionToSyncFrameworks($job);
            }
        }
    }

    private function transitionToSyncFrameworks(GlobalSyncJob $job): void
    {
        $frameworks = $this->productRepository->findByResolverSource(ResolverSource::EndOfLife);
        $total = \count($frameworks);
        $job->startStep(GlobalSyncStep::SyncFrameworks, $total);
        $this->repository->save($job);
        $this->publishProgressFromValues($job->getId()->toRfc4122(), $job, $job->getStepProgress(), $job->getStepTotal(), null);

        if ($total > 0) {
            $this->commandBus->dispatch(new SyncProductVersionsCommand(
                syncId: $job->getId()->toRfc4122(),
                resolverSource: ResolverSource::EndOfLife,
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

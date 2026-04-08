<?php

declare(strict_types=1);

namespace App\Coverage\Application\CommandHandler;

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Application\Command\FetchProjectCoverageCommand;
use App\Coverage\Domain\Model\CoverageSnapshot;
use App\Coverage\Domain\Model\CoverageSource;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use App\Coverage\Infrastructure\Provider\CoverageProviderRegistry;
use App\Shared\Domain\Event\ProjectCoverageFetchedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class FetchProjectCoverageHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CoverageProviderRegistry $providerRegistry,
        private CoverageSnapshotRepositoryInterface $snapshotRepository,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(FetchProjectCoverageCommand $command): void
    {
        $project = $this->projectRepository->findById(Uuid::fromString($command->projectId));
        if ($project === null) {
            return;
        }

        $provider = $project->getProvider();
        $coveragePercent = null;

        if ($provider !== null) {
            $coverageProvider = $this->providerRegistry->resolve($provider->getType());
            if ($coverageProvider !== null) {
                $result = $coverageProvider->fetchCoverage($project);
                if ($result !== null) {
                    $jobsData = $result->jobs !== []
                        ? \array_map(
                            static fn (\App\Coverage\Domain\ValueObject\JobCoverage $j): array => [
                                'name' => $j->name,
                                'percent' => $j->percent,
                            ],
                            $result->jobs,
                        )
                        : null;
                    $snapshot = CoverageSnapshot::create(
                        projectId: $project->getId(),
                        commitHash: $result->commitHash,
                        coveragePercent: $result->coveragePercent,
                        source: CoverageSource::fromProviderType($provider->getType()),
                        ref: $result->ref,
                        pipelineId: $result->pipelineId,
                        jobs: $jobsData,
                    );
                    $this->snapshotRepository->save($snapshot);
                    $coveragePercent = $result->coveragePercent;
                }
            }
        }

        $this->eventBus->dispatch(new ProjectCoverageFetchedEvent(
            projectId: $project->getId()->toRfc4122(),
            syncId: $command->syncId,
            projectName: $project->getName(),
            coveragePercent: $coveragePercent,
        ));
    }
}

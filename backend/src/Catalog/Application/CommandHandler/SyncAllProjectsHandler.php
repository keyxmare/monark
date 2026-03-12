<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\Command\SyncAllProjectsCommand;
use App\Catalog\Application\Command\SyncMergeRequestsCommand;
use App\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Catalog\Application\DTO\SyncJobOutput;
use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncAllProjectsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private ProviderRepositoryInterface $providerRepository,
        private SyncJobRepositoryInterface $syncJobRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(SyncAllProjectsCommand $command): SyncJobOutput
    {
        if ($command->providerId !== null) {
            $provider = $this->providerRepository->findById(Uuid::fromString($command->providerId));
            if ($provider === null) {
                throw NotFoundException::forEntity('Provider', $command->providerId);
            }
            $projects = $this->projectRepository->findByProviderId($provider->getId());
        } else {
            $projects = $this->projectRepository->findAllWithProvider();
        }

        $providerId = $command->providerId !== null ? Uuid::fromString($command->providerId) : null;
        $syncJob = SyncJob::create(\count($projects), $providerId);
        $this->syncJobRepository->save($syncJob);
        $syncJobId = $syncJob->getId()->toRfc4122();

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
                new SyncMergeRequestsCommand($projectId, $command->force, $syncJobId),
                [new DispatchAfterCurrentBusStamp()],
            );
        }

        return new SyncJobOutput(
            id: $syncJobId,
            projectsCount: \count($projects),
            startedAt: $syncJob->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}

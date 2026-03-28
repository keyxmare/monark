<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Catalog\Domain\Event\ProjectMetadataSyncedEvent;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\ProviderStatus;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncProjectMetadataHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private ProviderRepositoryInterface $providerRepository,
        private GitProviderFactoryInterface $gitProviderFactory,
        private MessageBusInterface $eventBus,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(SyncProjectMetadataCommand $command): void
    {
        $project = $this->projectRepository->findById(Uuid::fromString($command->projectId));
        if ($project === null) {
            return;
        }

        $provider = $project->getProvider();
        if ($provider === null || $project->getExternalId() === null) {
            return;
        }

        $client = $this->gitProviderFactory->create($provider);

        try {
            $remote = $client->getProject($provider, $project->getExternalId());
        } catch (Throwable $e) {
            $this->logger->error('Metadata sync failed for project {project}: {error}', [
                'project' => $command->projectId,
                'error' => $e->getMessage(),
            ]);
            if ($provider->getStatus() !== ProviderStatus::Error) {
                $provider->markError();
                $this->providerRepository->save($provider);
            }

            return;
        }

        $changedFields = [];

        if ($remote->name !== $project->getName()) {
            $changedFields[] = 'name';
        }
        if ($remote->description !== $project->getDescription()) {
            $changedFields[] = 'description';
        }
        if ($remote->defaultBranch !== $project->getDefaultBranch()) {
            $changedFields[] = 'defaultBranch';
        }

        $remoteVisibility = ProjectVisibility::tryFrom($remote->visibility);
        if ($remoteVisibility !== null && $remoteVisibility !== $project->getVisibility()) {
            $changedFields[] = 'visibility';
        }

        if ($changedFields === []) {
            return;
        }

        $project->update(
            name: $remote->name,
            description: $remote->description,
            defaultBranch: $remote->defaultBranch,
            visibility: $remoteVisibility,
        );

        $this->projectRepository->save($project);

        $this->eventBus->dispatch(new ProjectMetadataSyncedEvent(
            projectId: $command->projectId,
            changedFields: $changedFields,
        ));
    }
}

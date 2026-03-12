<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Catalog\Domain\Event\ProjectMetadataSyncedEvent;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncProjectMetadataHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private GitProviderFactory $gitProviderFactory,
        private MessageBusInterface $eventBus,
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
        $remote = $client->getProject($provider, $project->getExternalId());

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

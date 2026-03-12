<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\SyncMergeRequestsCommand;
use App\Catalog\Domain\Event\MergeRequestsSyncedEvent;
use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncMergeRequestsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private MergeRequestRepositoryInterface $mergeRequestRepository,
        private GitProviderFactory $gitProviderFactory,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(SyncMergeRequestsCommand $command): void
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

        $created = 0;
        $updated = 0;
        $projectId = $project->getId();
        $page = 1;
        $perPage = 100;

        do {
            $remoteMRs = $client->listMergeRequests($provider, $project->getExternalId(), null, $page, $perPage);

            foreach ($remoteMRs as $remoteMR) {
                $existing = $this->mergeRequestRepository->findByExternalIdAndProject($remoteMR->externalId, $projectId);
                $status = MergeRequestStatus::tryFrom($remoteMR->status) ?? MergeRequestStatus::Open;

                if ($existing !== null) {
                    $existing->update(
                        title: $remoteMR->title,
                        description: $remoteMR->description,
                        status: $status,
                        additions: $remoteMR->additions,
                        deletions: $remoteMR->deletions,
                        reviewers: $remoteMR->reviewers,
                        labels: $remoteMR->labels,
                        mergedAt: $remoteMR->mergedAt !== null ? new \DateTimeImmutable($remoteMR->mergedAt) : null,
                        closedAt: $remoteMR->closedAt !== null ? new \DateTimeImmutable($remoteMR->closedAt) : null,
                    );
                    $this->mergeRequestRepository->save($existing);
                    ++$updated;
                } else {
                    $mr = MergeRequest::create(
                        externalId: $remoteMR->externalId,
                        title: $remoteMR->title,
                        description: $remoteMR->description,
                        sourceBranch: $remoteMR->sourceBranch,
                        targetBranch: $remoteMR->targetBranch,
                        status: $status,
                        author: $remoteMR->author,
                        url: $remoteMR->url,
                        additions: $remoteMR->additions,
                        deletions: $remoteMR->deletions,
                        reviewers: $remoteMR->reviewers,
                        labels: $remoteMR->labels,
                        mergedAt: $remoteMR->mergedAt !== null ? new \DateTimeImmutable($remoteMR->mergedAt) : null,
                        closedAt: $remoteMR->closedAt !== null ? new \DateTimeImmutable($remoteMR->closedAt) : null,
                        project: $project,
                    );
                    $this->mergeRequestRepository->save($mr);
                    ++$created;
                }
            }

            ++$page;
        } while (\count($remoteMRs) === $perPage);

        $this->eventBus->dispatch(new MergeRequestsSyncedEvent(
            projectId: $command->projectId,
            created: $created,
            updated: $updated,
        ));
    }
}

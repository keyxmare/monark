<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\CommandHandler;

use App\Monitoring\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Monitoring\Catalog\Domain\Event\ProjectActivityCacheRefreshed;
use App\Monitoring\Catalog\Domain\Event\ProjectMetadataSyncedEvent;
use App\Monitoring\Catalog\Domain\Model\ProjectVisibility;
use App\Monitoring\Catalog\Domain\Model\ProviderStatus;
use App\Monitoring\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Monitoring\Catalog\Domain\Port\GitProviderInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProviderRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncProjectMetadataHandler
{
    private const int ACTIVITY_WINDOW_DAYS = 30;

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
        $externalId = $project->getExternalId();

        try {
            $remote = $client->getProject($provider, $externalId);
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
        $remoteVisibility = ProjectVisibility::tryFrom($remote->visibility);

        if ($remote->name !== $project->getName()) {
            $changedFields[] = 'name';
        }
        if ($remote->description !== $project->getDescription()) {
            $changedFields[] = 'description';
        }
        if ($remoteVisibility !== null && $remoteVisibility !== $project->getVisibility()) {
            $changedFields[] = 'visibility';
        }

        if ($changedFields !== []) {
            $project->update(
                name: $remote->name,
                description: $remote->description,
                visibility: $remoteVisibility,
            );
        }

        $palette = $this->fetchLanguages($client, $provider, $externalId, $command->projectId);
        [$series, $lastAt, $lastSha] = $this->fetchCommitSeries($client, $provider, $externalId, $command->projectId);

        if ($palette !== null || $series !== null) {
            $project->updateActivityCache($palette, $series, $lastAt, $lastSha);
        }

        $project->markSynced();
        $this->projectRepository->save($project);

        if ($palette !== null || $series !== null) {
            $this->eventBus->dispatch(new ProjectActivityCacheRefreshed($command->projectId));
        }

        if ($changedFields !== []) {
            $this->eventBus->dispatch(new ProjectMetadataSyncedEvent(
                projectId: $command->projectId,
                changedFields: $changedFields,
            ));
        }
    }

    /**
     * @return array<string, int>|null null when the call fails — caller preserves the previous cache
     */
    private function fetchLanguages(
        GitProviderInterface $client,
        \App\Monitoring\Catalog\Domain\Model\Provider $provider,
        string $externalId,
        string $projectId,
    ): ?array {
        try {
            return $client->getRepositoryLanguages($provider, $externalId);
        } catch (Throwable $e) {
            $this->logger->warning('Language sync failed for project {project}: {error}', [
                'project' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{0: list<int>|null, 1: ?DateTimeImmutable, 2: ?string} series, lastAt, lastSha — null series when the call fails
     */
    private function fetchCommitSeries(
        GitProviderInterface $client,
        \App\Monitoring\Catalog\Domain\Model\Provider $provider,
        string $externalId,
        string $projectId,
    ): array {
        $utc = new DateTimeZone('UTC');
        $now = new DateTimeImmutable('now', $utc);
        $since = $now->modify(\sprintf('-%d days', self::ACTIVITY_WINDOW_DAYS - 1))->setTime(0, 0, 0);

        try {
            $commits = $client->getRecentCommits($provider, $externalId, $since);
        } catch (Throwable $e) {
            $this->logger->warning('Commit sync failed for project {project}: {error}', [
                'project' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return [null, null, null];
        }

        $buckets = [];
        for ($i = 0; $i < self::ACTIVITY_WINDOW_DAYS; ++$i) {
            $dayKey = $now->modify(\sprintf('-%d days', self::ACTIVITY_WINDOW_DAYS - 1 - $i))->format('Y-m-d');
            $buckets[$dayKey] = 0;
        }

        $latestAt = null;
        $latestSha = null;
        foreach ($commits as $commit) {
            $authoredUtc = $commit->authoredAt->setTimezone($utc);
            $dayKey = $authoredUtc->format('Y-m-d');
            if (\array_key_exists($dayKey, $buckets)) {
                ++$buckets[$dayKey];
            }
            if ($latestAt === null || $authoredUtc > $latestAt) {
                $latestAt = $authoredUtc;
                $latestSha = $commit->sha;
            }
        }

        $series = \array_values($buckets);

        return [$series, $latestAt, $latestSha];
    }
}

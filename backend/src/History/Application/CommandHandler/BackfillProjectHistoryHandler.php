<?php

declare(strict_types=1);

namespace App\History\Application\CommandHandler;

use App\Catalog\Domain\Model\RemoteCommit;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Port\ProjectScannerInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\History\Application\Command\BackfillProjectHistoryCommand;
use App\History\Application\Command\RecordProjectSnapshotCommand;
use App\History\Domain\Model\SnapshotSource;
use App\Shared\Domain\Exception\NotFoundException;
use DomainException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class BackfillProjectHistoryHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private GitProviderFactoryInterface $gitProviderFactory,
        private ProjectScannerInterface $projectScanner,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(BackfillProjectHistoryCommand $command): void
    {
        $project = $this->projectRepository->findById(Uuid::fromString($command->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $command->projectId);
        }

        $provider = $project->getProvider();
        $externalId = $project->getExternalId();
        if ($provider === null || $externalId === null) {
            throw new DomainException(\sprintf('Project "%s" is not linked to a provider.', $command->projectId));
        }

        $client = $this->gitProviderFactory->create($provider);
        $commits = $client->listCommits(
            provider: $provider,
            externalProjectId: $externalId,
            ref: $project->getDefaultBranch(),
            since: $command->since,
            until: $command->until,
            perPage: 100,
        );

        if ($commits === []) {
            $this->publishProgress($command->projectId, 0, 0);

            return;
        }

        $sampled = $this->sampleCommitsByInterval($commits, $command->intervalDays);
        $total = \count($sampled);
        $processed = 0;

        foreach ($sampled as $commit) {
            $scanResult = $this->projectScanner->scan($project, $commit->sha);

            $this->commandBus->dispatch(new RecordProjectSnapshotCommand(
                projectId: $command->projectId,
                commitSha: $commit->sha,
                snapshotDate: $commit->date,
                source: SnapshotSource::Backfill,
                scanResult: $scanResult,
            ));

            ++$processed;
            $this->publishProgress($command->projectId, $processed, $total);
        }
    }

    /**
     * @param list<RemoteCommit> $commits
     * @return list<RemoteCommit>
     */
    private function sampleCommitsByInterval(array $commits, int $intervalDays): array
    {
        if ($commits === []) {
            return [];
        }

        \usort($commits, static fn (RemoteCommit $a, RemoteCommit $b): int => $a->date <=> $b->date);

        $intervalSeconds = \max(1, $intervalDays) * 86400;
        $sampled = [];
        $lastTs = null;

        foreach ($commits as $commit) {
            $ts = $commit->date->getTimestamp();
            if ($lastTs === null || ($ts - $lastTs) >= $intervalSeconds) {
                $sampled[] = $commit;
                $lastTs = $ts;
            }
        }

        return $sampled;
    }

    private function publishProgress(string $projectId, int $processed, int $total): void
    {
        try {
            $this->mercureHub->publish(new Update(
                \sprintf('/project/%s/backfill', $projectId),
                (string) \json_encode([
                    'projectId' => $projectId,
                    'processed' => $processed,
                    'total' => $total,
                ]),
            ));
        } catch (Throwable $e) {
            $this->logger->warning('Failed to publish backfill progress to Mercure', [
                'projectId' => $projectId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

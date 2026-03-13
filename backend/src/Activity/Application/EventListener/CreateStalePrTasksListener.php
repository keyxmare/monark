<?php

declare(strict_types=1);

namespace App\Activity\Application\EventListener;

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Shared\Domain\Event\MergeRequestsSyncedEvent;
use App\Shared\Domain\Port\MergeRequestReaderPort;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class CreateStalePrTasksListener
{
    private const int STALE_DAYS = 7;
    private const int VERY_STALE_DAYS = 30;

    public function __construct(
        private MergeRequestReaderPort $mergeRequestReader,
        private SyncTaskRepositoryInterface $syncTaskRepository,
    ) {
    }

    public function __invoke(MergeRequestsSyncedEvent $event): void
    {
        $projectId = Uuid::fromString($event->projectId);
        $now = new DateTimeImmutable();

        $activeMRs = $this->mergeRequestReader->findActiveByProjectId($projectId);

        foreach ($activeMRs as $mr) {
            $daysSinceUpdate = $now->diff($mr->updatedAt)->days;

            if ($daysSinceUpdate < self::STALE_DAYS) {
                continue;
            }

            $severity = $daysSinceUpdate >= self::VERY_STALE_DAYS
                ? SyncTaskSeverity::High
                : SyncTaskSeverity::Medium;

            $title = \sprintf('Stale MR #%s: %s', $mr->externalId, $mr->title);
            $description = \sprintf(
                'MR #%s "%s" by %s has been %s for %d days without activity.',
                $mr->externalId,
                $mr->title,
                $mr->author,
                $mr->status,
                $daysSinceUpdate,
            );
            $metadata = [
                'externalId' => $mr->externalId,
                'title' => $mr->title,
                'author' => $mr->author,
                'status' => $mr->status,
                'daysSinceUpdate' => $daysSinceUpdate,
                'url' => $mr->url,
            ];

            $existing = $this->syncTaskRepository->findOpenByProjectAndTypeAndKey(
                $projectId,
                SyncTaskType::StalePr,
                $mr->externalId,
            );

            if ($existing !== null) {
                $existing->updateInfo($severity, $title, $description, $metadata);
                $this->syncTaskRepository->save($existing);
                continue;
            }

            $task = SyncTask::create(
                type: SyncTaskType::StalePr,
                severity: $severity,
                title: $title,
                description: $description,
                metadata: $metadata,
                projectId: $projectId,
            );
            $this->syncTaskRepository->save($task);
        }
    }
}

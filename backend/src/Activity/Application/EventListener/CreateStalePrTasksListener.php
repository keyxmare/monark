<?php

declare(strict_types=1);

namespace App\Activity\Application\EventListener;

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Catalog\Domain\Event\MergeRequestsSyncedEvent;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class CreateStalePrTasksListener
{
    private const int STALE_DAYS = 7;
    private const int VERY_STALE_DAYS = 30;

    public function __construct(
        private MergeRequestRepositoryInterface $mergeRequestRepository,
        private SyncTaskRepositoryInterface $syncTaskRepository,
    ) {
    }

    public function __invoke(MergeRequestsSyncedEvent $event): void
    {
        $projectId = Uuid::fromString($event->projectId);
        $now = new \DateTimeImmutable();

        $openMRs = $this->mergeRequestRepository->findByProjectId($projectId, 1, 100, MergeRequestStatus::Open);
        $draftMRs = $this->mergeRequestRepository->findByProjectId($projectId, 1, 100, MergeRequestStatus::Draft);

        foreach ([...$openMRs, ...$draftMRs] as $mr) {
            $daysSinceUpdate = $now->diff($mr->getUpdatedAt())->days;

            if ($daysSinceUpdate < self::STALE_DAYS) {
                continue;
            }

            $severity = $daysSinceUpdate >= self::VERY_STALE_DAYS
                ? SyncTaskSeverity::High
                : SyncTaskSeverity::Medium;

            $title = \sprintf('Stale MR #%s: %s', $mr->getExternalId(), $mr->getTitle());
            $description = \sprintf(
                'MR #%s "%s" by %s has been %s for %d days without activity.',
                $mr->getExternalId(),
                $mr->getTitle(),
                $mr->getAuthor(),
                $mr->getStatus()->value,
                $daysSinceUpdate,
            );
            $metadata = [
                'externalId' => $mr->getExternalId(),
                'title' => $mr->getTitle(),
                'author' => $mr->getAuthor(),
                'status' => $mr->getStatus()->value,
                'daysSinceUpdate' => $daysSinceUpdate,
                'url' => $mr->getUrl(),
            ];

            $existing = $this->syncTaskRepository->findOpenByProjectAndTypeAndKey(
                $projectId,
                SyncTaskType::StalePr,
                $mr->getExternalId(),
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

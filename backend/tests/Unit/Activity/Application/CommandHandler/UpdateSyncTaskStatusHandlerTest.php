<?php

declare(strict_types=1);

use App\Activity\Application\Command\UpdateSyncTaskStatusCommand;
use App\Activity\Application\CommandHandler\UpdateSyncTaskStatusHandler;
use App\Activity\Application\DTO\SyncTaskOutput;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubUpdateSyncTaskRepo(?SyncTask $task = null): object
{
    return new class ($task) implements SyncTaskRepositoryInterface {
        public ?SyncTask $lastSaved = null;
        public function __construct(private readonly ?SyncTask $task)
        {
        }
        public function findById(Uuid $id): ?SyncTask
        {
            return $this->task;
        }
        public function findFiltered(?SyncTaskStatus $status = null, ?SyncTaskType $type = null, ?SyncTaskSeverity $severity = null, ?Uuid $projectId = null, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function countFiltered(?SyncTaskStatus $status = null, ?SyncTaskType $type = null, ?SyncTaskSeverity $severity = null, ?Uuid $projectId = null): int
        {
            return 0;
        }
        public function findOpenByProjectAndTypeAndKey(Uuid $projectId, SyncTaskType $type, string $metadataKey): ?SyncTask
        {
            return null;
        }
        public function countGroupedByType(): array
        {
            return [];
        }
        public function countGroupedBySeverity(): array
        {
            return [];
        }
        public function countGroupedByStatus(): array
        {
            return [];
        }
        public function save(SyncTask $syncTask): void
        {
            $this->lastSaved = $syncTask;
        }
    };
}

describe('UpdateSyncTaskStatusHandler', function () {
    it('changes status to acknowledged', function () {
        $task = SyncTask::create(
            type: SyncTaskType::OutdatedDependency,
            severity: SyncTaskSeverity::High,
            title: 'Test task',
            description: 'Test desc',
            metadata: ['dependencyName' => 'foo'],
            projectId: Uuid::v7(),
        );

        $repo = \stubUpdateSyncTaskRepo($task);
        $handler = new UpdateSyncTaskStatusHandler($repo);
        $result = $handler(new UpdateSyncTaskStatusCommand($task->getId()->toRfc4122(), 'acknowledged'));

        expect($result)->toBeInstanceOf(SyncTaskOutput::class);
        expect($result->status)->toBe('acknowledged');
        expect($task->getStatus())->toBe(SyncTaskStatus::Acknowledged);
    });

    it('sets resolvedAt when resolved', function () {
        $task = SyncTask::create(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::Critical,
            title: 'Vuln task',
            description: 'Fix it',
            metadata: ['cveId' => 'CVE-2024-0001'],
            projectId: Uuid::v7(),
        );

        $repo = \stubUpdateSyncTaskRepo($task);
        $handler = new UpdateSyncTaskStatusHandler($repo);
        $handler(new UpdateSyncTaskStatusCommand($task->getId()->toRfc4122(), 'resolved'));

        expect($task->getStatus())->toBe(SyncTaskStatus::Resolved);
        expect($task->getResolvedAt())->not->toBeNull();
    });

    it('throws not found for unknown task', function () {
        $repo = \stubUpdateSyncTaskRepo(null);
        $handler = new UpdateSyncTaskStatusHandler($repo);

        $handler(new UpdateSyncTaskStatusCommand(Uuid::v7()->toRfc4122(), 'acknowledged'));
    })->throws(\App\Shared\Domain\Exception\NotFoundException::class);
});

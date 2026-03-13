<?php

declare(strict_types=1);

use App\Activity\Application\DTO\SyncTaskListOutput;
use App\Activity\Application\Query\ListSyncTasksQuery;
use App\Activity\Application\QueryHandler\ListSyncTasksHandler;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListSyncTaskRepo(array $tasks = [], int $count = 0): SyncTaskRepositoryInterface
{
    return new class ($tasks, $count) implements SyncTaskRepositoryInterface {
        public function __construct(private readonly array $tasks, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?SyncTask
        {
            return null;
        }
        public function findFiltered(?SyncTaskStatus $status = null, ?SyncTaskType $type = null, ?SyncTaskSeverity $severity = null, ?Uuid $projectId = null, int $page = 1, int $perPage = 20): array
        {
            return $this->tasks;
        }
        public function countFiltered(?SyncTaskStatus $status = null, ?SyncTaskType $type = null, ?SyncTaskSeverity $severity = null, ?Uuid $projectId = null): int
        {
            return $this->count;
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
        }
    };
}

describe('ListSyncTasksHandler', function () {
    it('returns paginated sync tasks', function () {
        $t1 = SyncTask::create(SyncTaskType::OutdatedDependency, SyncTaskSeverity::High, 'Task 1', 'Desc 1', ['dependencyName' => 'a'], Uuid::v7());
        $t2 = SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::Critical, 'Task 2', 'Desc 2', ['cveId' => 'CVE-1'], Uuid::v7());

        $handler = new ListSyncTasksHandler(\stubListSyncTaskRepo([$t1, $t2], 2));
        $result = $handler(new ListSyncTasksQuery());

        expect($result)->toBeInstanceOf(SyncTaskListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no tasks match', function () {
        $handler = new ListSyncTasksHandler(\stubListSyncTaskRepo([], 0));
        $result = $handler(new ListSyncTasksQuery(status: 'open'));

        expect($result->pagination->items)->toHaveCount(0);
        expect($result->pagination->total)->toBe(0);
    });
});

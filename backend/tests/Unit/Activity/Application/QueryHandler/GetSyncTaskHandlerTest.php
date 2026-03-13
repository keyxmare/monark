<?php

declare(strict_types=1);

use App\Activity\Application\DTO\SyncTaskOutput;
use App\Activity\Application\Query\GetSyncTaskQuery;
use App\Activity\Application\QueryHandler\GetSyncTaskHandler;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubGetSyncTaskRepo(?SyncTask $task = null): SyncTaskRepositoryInterface
{
    return new class ($task) implements SyncTaskRepositoryInterface {
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
        }
    };
}

describe('GetSyncTaskHandler', function () {
    it('returns sync task output', function () {
        $task = SyncTask::create(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::Critical,
            title: 'CVE-2024-0001',
            description: 'Critical vuln',
            metadata: ['cveId' => 'CVE-2024-0001'],
            projectId: Uuid::v7(),
        );

        $handler = new GetSyncTaskHandler(\stubGetSyncTaskRepo($task));
        $result = $handler(new GetSyncTaskQuery($task->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(SyncTaskOutput::class);
        expect($result->type)->toBe('vulnerability');
        expect($result->severity)->toBe('critical');
        expect($result->title)->toBe('CVE-2024-0001');
    });

    it('throws not found for unknown task', function () {
        $handler = new GetSyncTaskHandler(\stubGetSyncTaskRepo(null));
        $handler(new GetSyncTaskQuery(Uuid::v7()->toRfc4122()));
    })->throws(\App\Shared\Domain\Exception\NotFoundException::class);
});

<?php

declare(strict_types=1);

use App\Activity\Application\EventListener\CreateStalePrTasksListener;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Shared\Domain\DTO\MergeRequestReadDTO;
use App\Shared\Domain\Event\MergeRequestsSyncedEvent;
use App\Shared\Domain\Port\MergeRequestReaderPort;
use Symfony\Component\Uid\Uuid;

function stubStalePrMRPort(array $activeMRs = []): MergeRequestReaderPort
{
    return new class ($activeMRs) implements MergeRequestReaderPort {
        public function __construct(private readonly array $activeMRs)
        {
        }
        public function findActiveByProjectId(Uuid $projectId): array
        {
            return $this->activeMRs;
        }
    };
}

function spyStalePrSyncTaskRepo(?SyncTask $existing = null): object
{
    return new class ($existing) implements SyncTaskRepositoryInterface {
        /** @var list<SyncTask> */
        public array $saved = [];
        public ?SyncTaskType $lastLookupType = null;
        public ?string $lastLookupKey = null;

        public function __construct(private readonly ?SyncTask $existing)
        {
        }
        public function findById(Uuid $id): ?SyncTask
        {
            return null;
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
            $this->lastLookupType = $type;
            $this->lastLookupKey = $metadataKey;

            return $this->existing;
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
            $this->saved[] = $syncTask;
        }
    };
}

function createStaleMRDTO(string $status, int $daysOld, string $externalId = '1'): MergeRequestReadDTO
{
    return new MergeRequestReadDTO(
        externalId: $externalId,
        title: 'Stale MR',
        author: 'dev',
        status: $status,
        url: 'https://gitlab.com/test/-/merge_requests/' . $externalId,
        updatedAt: new DateTimeImmutable("-{$daysOld} days"),
    );
}

describe('CreateStalePrTasksListener', function () {
    it('creates medium severity task for MR stale > 7 days with exact title and description', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 10, '42');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        $task = $syncTaskRepo->saved[0];
        expect($task->getType())->toBe(SyncTaskType::StalePr);
        expect($task->getSeverity())->toBe(SyncTaskSeverity::Medium);
        expect($task->getTitle())->toBe('Stale MR #42: Stale MR');
        expect($task->getDescription())->toContain('MR #42');
        expect($task->getDescription())->toContain('"Stale MR"');
        expect($task->getDescription())->toContain('by dev');
        expect($task->getDescription())->toContain('open');
        expect($task->getDescription())->toContain('10 days');
        expect($task->getDescription())->toContain('without activity');
        expect($task->getMetadata())->toBe([
            'externalId' => '42',
            'title' => 'Stale MR',
            'author' => 'dev',
            'status' => 'open',
            'daysSinceUpdate' => 10,
            'url' => 'https://gitlab.com/test/-/merge_requests/42',
        ]);
        expect($task->getProjectId()->toRfc4122())->toBe($projectId->toRfc4122());
    });

    it('uses externalId as lookup key', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 10, '55');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->lastLookupType)->toBe(SyncTaskType::StalePr);
        expect($syncTaskRepo->lastLookupKey)->toBe('55');
    });

    it('creates high severity task for MR stale >= 30 days', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 30, '99');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::High);
    });

    it('creates high severity task for MR stale > 30 days', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 45, '99');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::High);
    });

    it('creates medium severity for exactly 29 days (below very stale threshold)', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 29, '88');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::Medium);
    });

    it('creates task for MR at exactly 7 days stale', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 7, '77');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::Medium);
    });

    it('skips MR updated less than 7 days ago', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 3, '10');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toBeEmpty();
    });

    it('skips MR at exactly 6 days (just below stale threshold)', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 6, '66');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toBeEmpty();
    });

    it('detects stale draft MRs', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('draft', 15, '77');
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getMetadata()['status'])->toBe('draft');
        expect($syncTaskRepo->saved[0]->getDescription())->toContain('draft');
    });

    it('updates existing task instead of creating duplicate', function () {
        $projectId = Uuid::v7();
        $mr = \createStaleMRDTO('open', 10, '42');
        $existingTask = SyncTask::create(
            type: SyncTaskType::StalePr,
            severity: SyncTaskSeverity::Low,
            title: 'Old title',
            description: 'Old desc',
            metadata: ['externalId' => '42'],
            projectId: $projectId,
        );

        $syncTaskRepo = \spyStalePrSyncTaskRepo($existingTask);

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0])->toBe($existingTask);
        expect($existingTask->getSeverity())->toBe(SyncTaskSeverity::Medium);
        expect($existingTask->getTitle())->toBe('Stale MR #42: Stale MR');
        expect($existingTask->getMetadata()['daysSinceUpdate'])->toBe(10);
    });

    it('handles multiple MRs with mixed staleness', function () {
        $projectId = Uuid::v7();
        $mrs = [
            \createStaleMRDTO('open', 2, '1'),
            \createStaleMRDTO('open', 10, '2'),
            \createStaleMRDTO('open', 35, '3'),
        ];
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort($mrs),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(2);
    });

    it('creates no tasks when no active MRs exist', function () {
        $projectId = Uuid::v7();
        $syncTaskRepo = \spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            \stubStalePrMRPort([]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $projectId->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toBeEmpty();
    });
});

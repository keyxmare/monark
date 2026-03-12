<?php

declare(strict_types=1);

use App\Activity\Application\EventListener\CreateStalePrTasksListener;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Catalog\Domain\Event\MergeRequestsSyncedEvent;
use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubStalePrMRRepo(array $openMRs = [], array $draftMRs = []): MergeRequestRepositoryInterface
{
    return new class ($openMRs, $draftMRs) implements MergeRequestRepositoryInterface {
        public function __construct(
            private readonly array $openMRs,
            private readonly array $draftMRs,
        ) {}
        public function findById(Uuid $id): ?MergeRequest { return null; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, array $statuses = [], ?string $author = null): array
        {
            $result = [];
            if ($statuses === [] || \in_array(MergeRequestStatus::Open, $statuses, true)) {
                $result = [...$result, ...$this->openMRs];
            }
            if ($statuses === [] || \in_array(MergeRequestStatus::Draft, $statuses, true)) {
                $result = [...$result, ...$this->draftMRs];
            }
            return $result;
        }
        public function findByExternalIdAndProject(string $externalId, Uuid $projectId): ?MergeRequest { return null; }
        public function countByProjectId(Uuid $projectId, array $statuses = [], ?string $author = null): int { return 0; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(MergeRequest $mergeRequest): void {}
        public function delete(MergeRequest $mergeRequest): void {}
    };
}

function spyStalePrSyncTaskRepo(?SyncTask $existing = null): object
{
    return new class ($existing) implements SyncTaskRepositoryInterface {
        /** @var list<SyncTask> */
        public array $saved = [];
        public function __construct(private readonly ?SyncTask $existing) {}
        public function findById(Uuid $id): ?SyncTask { return null; }
        public function findFiltered(?SyncTaskStatus $status = null, ?SyncTaskType $type = null, ?SyncTaskSeverity $severity = null, ?Uuid $projectId = null, int $page = 1, int $perPage = 20): array { return []; }
        public function countFiltered(?SyncTaskStatus $status = null, ?SyncTaskType $type = null, ?SyncTaskSeverity $severity = null, ?Uuid $projectId = null): int { return 0; }
        public function findOpenByProjectAndTypeAndKey(Uuid $projectId, SyncTaskType $type, string $metadataKey): ?SyncTask { return $this->existing; }
        public function countGroupedByType(): array { return []; }
        public function countGroupedBySeverity(): array { return []; }
        public function countGroupedByStatus(): array { return []; }
        public function save(SyncTask $syncTask): void { $this->saved[] = $syncTask; }
    };
}

function createStaleMR(MergeRequestStatus $status, int $daysOld, string $externalId = '1'): MergeRequest
{
    $project = ProjectFactory::create();
    $mr = MergeRequest::create(
        externalId: $externalId,
        title: 'Stale MR',
        description: null,
        sourceBranch: 'feature/old',
        targetBranch: 'main',
        status: $status,
        author: 'dev',
        url: 'https://gitlab.com/test/-/merge_requests/' . $externalId,
        additions: 10,
        deletions: 5,
        reviewers: [],
        labels: [],
        mergedAt: null,
        closedAt: null,
        project: $project,
    );

    $ref = new ReflectionProperty($mr, 'updatedAt');
    $ref->setValue($mr, new DateTimeImmutable("-{$daysOld} days"));

    return $mr;
}

describe('CreateStalePrTasksListener', function () {
    it('creates medium severity task for MR stale > 7 days', function () {
        $mr = createStaleMR(MergeRequestStatus::Open, 10, '42');
        $syncTaskRepo = spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            stubStalePrMRRepo(openMRs: [$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $mr->getProject()->getId()->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getType())->toBe(SyncTaskType::StalePr);
        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::Medium);
        expect($syncTaskRepo->saved[0]->getMetadata()['externalId'])->toBe('42');
        expect($syncTaskRepo->saved[0]->getMetadata()['daysSinceUpdate'])->toBe(10);
    });

    it('creates high severity task for MR stale > 30 days', function () {
        $mr = createStaleMR(MergeRequestStatus::Open, 45, '99');
        $syncTaskRepo = spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            stubStalePrMRRepo(openMRs: [$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $mr->getProject()->getId()->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::High);
    });

    it('skips MR updated less than 7 days ago', function () {
        $mr = createStaleMR(MergeRequestStatus::Open, 3, '10');
        $syncTaskRepo = spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            stubStalePrMRRepo(openMRs: [$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $mr->getProject()->getId()->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toBeEmpty();
    });

    it('detects stale draft MRs', function () {
        $mr = createStaleMR(MergeRequestStatus::Draft, 15, '77');
        $syncTaskRepo = spyStalePrSyncTaskRepo();

        $listener = new CreateStalePrTasksListener(
            stubStalePrMRRepo(draftMRs: [$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $mr->getProject()->getId()->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getMetadata()['status'])->toBe('draft');
    });

    it('updates existing task instead of creating duplicate', function () {
        $mr = createStaleMR(MergeRequestStatus::Open, 10, '42');
        $existingTask = SyncTask::create(
            type: SyncTaskType::StalePr,
            severity: SyncTaskSeverity::Low,
            title: 'Old title',
            description: 'Old desc',
            metadata: ['externalId' => '42'],
            projectId: $mr->getProject()->getId(),
        );

        $syncTaskRepo = spyStalePrSyncTaskRepo($existingTask);

        $listener = new CreateStalePrTasksListener(
            stubStalePrMRRepo(openMRs: [$mr]),
            $syncTaskRepo,
        );
        $listener(new MergeRequestsSyncedEvent(
            projectId: $mr->getProject()->getId()->toRfc4122(),
            created: 0,
            updated: 0,
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0])->toBe($existingTask);
        expect($existingTask->getSeverity())->toBe(SyncTaskSeverity::Medium);
    });
});

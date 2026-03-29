<?php

declare(strict_types=1);

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(SyncTaskRepositoryInterface::class);
});

function createSyncTask(
    SyncTaskType $type = SyncTaskType::Vulnerability,
    SyncTaskSeverity $severity = SyncTaskSeverity::High,
    string $title = 'Test Task',
    string $description = 'A test sync task',
    array $metadata = ['cveId' => 'CVE-2024-0001'],
    ?Uuid $projectId = null,
): SyncTask {
    return SyncTask::create(
        type: $type,
        severity: $severity,
        title: $title,
        description: $description,
        metadata: $metadata,
        projectId: $projectId ?? Uuid::v7(),
    );
}

describe('DoctrineSyncTaskRepository', function () {
    it('saves and finds a sync task by id', function () {
        $task = \createSyncTask();
        $this->repo->save($task);

        $found = $this->repo->findById($task->getId());

        expect($found)->not->toBeNull();
        expect($found->getTitle())->toBe('Test Task');
        expect($found->getType())->toBe(SyncTaskType::Vulnerability);
        expect($found->getSeverity())->toBe(SyncTaskSeverity::High);
        expect($found->getStatus())->toBe(SyncTaskStatus::Open);
        expect($found->getMetadata())->toBe(['cveId' => 'CVE-2024-0001']);
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(Uuid::v7()))->toBeNull();
    });

    it('filters by status', function () {
        $open = \createSyncTask(title: 'Open Task');
        $this->repo->save($open);

        $resolved = \createSyncTask(title: 'Resolved Task');
        $resolved->changeStatus(SyncTaskStatus::Resolved);
        $this->repo->save($resolved);

        $results = $this->repo->findFiltered(status: SyncTaskStatus::Open);
        expect($results)->toHaveCount(1);
        expect($results[0]->getTitle())->toBe('Open Task');
    });

    it('filters by type and severity', function () {
        $this->repo->save(\createSyncTask(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::Critical,
        ));
        $this->repo->save(\createSyncTask(
            type: SyncTaskType::OutdatedDependency,
            severity: SyncTaskSeverity::Low,
            metadata: ['dependencyName' => 'lodash'],
        ));
        $this->repo->save(\createSyncTask(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::Low,
            metadata: ['cveId' => 'CVE-2024-0002'],
        ));

        $results = $this->repo->findFiltered(type: SyncTaskType::Vulnerability);
        expect($results)->toHaveCount(2);

        $results = $this->repo->findFiltered(severity: SyncTaskSeverity::Critical);
        expect($results)->toHaveCount(1);

        $results = $this->repo->findFiltered(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::Low,
        );
        expect($results)->toHaveCount(1);
    });

    it('filters by project id', function () {
        $projectId = Uuid::v7();

        $this->repo->save(\createSyncTask(projectId: $projectId));
        $this->repo->save(\createSyncTask(projectId: $projectId));
        $this->repo->save(\createSyncTask());

        $results = $this->repo->findFiltered(projectId: $projectId);
        expect($results)->toHaveCount(2);
    });

    it('counts filtered tasks', function () {
        $projectId = Uuid::v7();

        $this->repo->save(\createSyncTask(
            type: SyncTaskType::Vulnerability,
            projectId: $projectId,
        ));
        $this->repo->save(\createSyncTask(
            type: SyncTaskType::OutdatedDependency,
            projectId: $projectId,
            metadata: ['dependencyName' => 'lodash'],
        ));
        $this->repo->save(\createSyncTask(type: SyncTaskType::Vulnerability));

        expect($this->repo->countFiltered(projectId: $projectId))->toBe(2);
        expect($this->repo->countFiltered(type: SyncTaskType::Vulnerability))->toBe(2);
        expect($this->repo->countFiltered(
            type: SyncTaskType::Vulnerability,
            projectId: $projectId,
        ))->toBe(1);
    });

    it('finds open task by project type and metadata key', function () {
        $projectId = Uuid::v7();

        $task = \createSyncTask(
            type: SyncTaskType::Vulnerability,
            metadata: ['cveId' => 'CVE-2024-9999'],
            projectId: $projectId,
        );
        $this->repo->save($task);

        // Different CVE - should not match
        $this->repo->save(\createSyncTask(
            type: SyncTaskType::Vulnerability,
            metadata: ['cveId' => 'CVE-2024-0000'],
            projectId: $projectId,
        ));

        $found = $this->repo->findOpenByProjectAndTypeAndKey(
            $projectId,
            SyncTaskType::Vulnerability,
            'CVE-2024-9999',
        );

        expect($found)->not->toBeNull();
        expect($found->getId()->equals($task->getId()))->toBeTrue();
    });

    it('does not find resolved task by project type and key', function () {
        $projectId = Uuid::v7();

        $task = \createSyncTask(
            type: SyncTaskType::Vulnerability,
            metadata: ['cveId' => 'CVE-2024-9999'],
            projectId: $projectId,
        );
        $task->changeStatus(SyncTaskStatus::Resolved);
        $this->repo->save($task);

        $found = $this->repo->findOpenByProjectAndTypeAndKey(
            $projectId,
            SyncTaskType::Vulnerability,
            'CVE-2024-9999',
        );

        expect($found)->toBeNull();
    });

    it('counts grouped by type', function () {
        $this->repo->save(\createSyncTask(type: SyncTaskType::Vulnerability));
        $this->repo->save(\createSyncTask(type: SyncTaskType::Vulnerability, metadata: ['cveId' => 'CVE-2024-0002']));
        $this->repo->save(\createSyncTask(
            type: SyncTaskType::OutdatedDependency,
            metadata: ['dependencyName' => 'lodash'],
        ));

        $grouped = $this->repo->countGroupedByType();

        expect($grouped)->toBeArray();
        $map = \array_column($grouped, 'count', 'label');
        expect($map[SyncTaskType::Vulnerability->value])->toBe(2);
        expect($map[SyncTaskType::OutdatedDependency->value])->toBe(1);
    });

    it('counts grouped by severity', function () {
        $this->repo->save(\createSyncTask(severity: SyncTaskSeverity::Critical));
        $this->repo->save(\createSyncTask(severity: SyncTaskSeverity::Critical, metadata: ['cveId' => 'CVE-2024-0002']));
        $this->repo->save(\createSyncTask(severity: SyncTaskSeverity::Low, metadata: ['cveId' => 'CVE-2024-0003']));

        $grouped = $this->repo->countGroupedBySeverity();

        $map = \array_column($grouped, 'count', 'label');
        expect($map[SyncTaskSeverity::Critical->value])->toBe(2);
        expect($map[SyncTaskSeverity::Low->value])->toBe(1);
    });

    it('counts grouped by status', function () {
        $open = \createSyncTask();
        $this->repo->save($open);

        $resolved = \createSyncTask(metadata: ['cveId' => 'CVE-2024-0002']);
        $resolved->changeStatus(SyncTaskStatus::Resolved);
        $this->repo->save($resolved);

        $dismissed = \createSyncTask(metadata: ['cveId' => 'CVE-2024-0003']);
        $dismissed->changeStatus(SyncTaskStatus::Dismissed);
        $this->repo->save($dismissed);

        $grouped = $this->repo->countGroupedByStatus();

        $map = \array_column($grouped, 'count', 'label');
        expect($map[SyncTaskStatus::Open->value])->toBe(1);
        expect($map[SyncTaskStatus::Resolved->value])->toBe(1);
        expect($map[SyncTaskStatus::Dismissed->value])->toBe(1);
    });
});

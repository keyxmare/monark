<?php

declare(strict_types=1);

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use Symfony\Component\Uid\Uuid;

describe('SyncTask', function () {
    it('creates with all fields', function () {
        $projectId = Uuid::v7();
        $task = SyncTask::create(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::Critical,
            title: 'CVE-2025-1234',
            description: 'Critical RCE vulnerability',
            metadata: ['cveId' => 'CVE-2025-1234'],
            projectId: $projectId,
        );

        expect($task->getId())->toBeInstanceOf(Uuid::class);
        expect($task->getType())->toBe(SyncTaskType::Vulnerability);
        expect($task->getSeverity())->toBe(SyncTaskSeverity::Critical);
        expect($task->getTitle())->toBe('CVE-2025-1234');
        expect($task->getDescription())->toBe('Critical RCE vulnerability');
        expect($task->getStatus())->toBe(SyncTaskStatus::Open);
        expect($task->getMetadata())->toBe(['cveId' => 'CVE-2025-1234']);
        expect($task->getProjectId()->equals($projectId))->toBeTrue();
        expect($task->getResolvedAt())->toBeNull();
        expect($task->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($task->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('defaults to open status', function () {
        $task = SyncTask::create(
            type: SyncTaskType::OutdatedDependency,
            severity: SyncTaskSeverity::Medium,
            title: 'Outdated lodash',
            description: 'lodash is outdated',
            metadata: ['dependencyName' => 'lodash'],
            projectId: Uuid::v7(),
        );

        expect($task->getStatus())->toBe(SyncTaskStatus::Open);
        expect($task->isOpen())->toBeTrue();
    });

    it('updates info', function () {
        $task = SyncTask::create(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::High,
            title: 'Original Title',
            description: 'Original description',
            metadata: ['cveId' => 'CVE-2025-0001'],
            projectId: Uuid::v7(),
        );

        $originalUpdatedAt = $task->getUpdatedAt();

        usleep(1000);

        $task->updateInfo(
            severity: SyncTaskSeverity::Critical,
            title: 'Updated Title',
            description: 'Updated description',
            metadata: ['cveId' => 'CVE-2025-0001', 'extra' => true],
        );

        expect($task->getSeverity())->toBe(SyncTaskSeverity::Critical);
        expect($task->getTitle())->toBe('Updated Title');
        expect($task->getDescription())->toBe('Updated description');
        expect($task->getMetadata())->toBe(['cveId' => 'CVE-2025-0001', 'extra' => true]);
        expect($task->getUpdatedAt() >= $originalUpdatedAt)->toBeTrue();
    });

    it('changes status to acknowledged and remains open', function () {
        $task = SyncTask::create(
            type: SyncTaskType::StalePr,
            severity: SyncTaskSeverity::Low,
            title: 'Stale PR',
            description: 'PR is stale',
            metadata: ['externalId' => '42'],
            projectId: Uuid::v7(),
        );

        $task->changeStatus(SyncTaskStatus::Acknowledged);

        expect($task->getStatus())->toBe(SyncTaskStatus::Acknowledged);
        expect($task->isOpen())->toBeTrue();
        expect($task->getResolvedAt())->toBeNull();
    });

    it('changes status to resolved and sets resolvedAt', function () {
        $task = SyncTask::create(
            type: SyncTaskType::NewDependency,
            severity: SyncTaskSeverity::Info,
            title: 'New dep',
            description: 'New dependency detected',
            metadata: ['dependencyName' => 'axios'],
            projectId: Uuid::v7(),
        );

        $task->changeStatus(SyncTaskStatus::Resolved);

        expect($task->getStatus())->toBe(SyncTaskStatus::Resolved);
        expect($task->isOpen())->toBeFalse();
        expect($task->getResolvedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('changes status to dismissed and sets resolvedAt', function () {
        $task = SyncTask::create(
            type: SyncTaskType::StackUpgrade,
            severity: SyncTaskSeverity::Medium,
            title: 'Stack upgrade',
            description: 'Upgrade available',
            metadata: ['language' => 'php', 'framework' => 'symfony'],
            projectId: Uuid::v7(),
        );

        $task->changeStatus(SyncTaskStatus::Dismissed);

        expect($task->getStatus())->toBe(SyncTaskStatus::Dismissed);
        expect($task->isOpen())->toBeFalse();
        expect($task->getResolvedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('returns metadata key for outdated dependency', function () {
        $task = SyncTask::create(
            type: SyncTaskType::OutdatedDependency,
            severity: SyncTaskSeverity::Medium,
            title: 'Outdated',
            description: 'Outdated dep',
            metadata: ['dependencyName' => 'symfony/console'],
            projectId: Uuid::v7(),
        );

        expect($task->getMetadataKey())->toBe('symfony/console');
    });

    it('returns metadata key for new dependency', function () {
        $task = SyncTask::create(
            type: SyncTaskType::NewDependency,
            severity: SyncTaskSeverity::Info,
            title: 'New',
            description: 'New dep',
            metadata: ['dependencyName' => 'react'],
            projectId: Uuid::v7(),
        );

        expect($task->getMetadataKey())->toBe('react');
    });

    it('returns metadata key for vulnerability', function () {
        $task = SyncTask::create(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::Critical,
            title: 'CVE',
            description: 'Vuln',
            metadata: ['cveId' => 'CVE-2025-9999'],
            projectId: Uuid::v7(),
        );

        expect($task->getMetadataKey())->toBe('CVE-2025-9999');
    });

    it('returns metadata key for stack upgrade', function () {
        $task = SyncTask::create(
            type: SyncTaskType::StackUpgrade,
            severity: SyncTaskSeverity::High,
            title: 'Upgrade',
            description: 'Upgrade stack',
            metadata: ['language' => 'php', 'framework' => 'laravel'],
            projectId: Uuid::v7(),
        );

        expect($task->getMetadataKey())->toBe('php:laravel');
    });

    it('returns metadata key for stale PR', function () {
        $task = SyncTask::create(
            type: SyncTaskType::StalePr,
            severity: SyncTaskSeverity::Low,
            title: 'Stale',
            description: 'Stale PR',
            metadata: ['externalId' => 'MR-100'],
            projectId: Uuid::v7(),
        );

        expect($task->getMetadataKey())->toBe('MR-100');
    });

    it('returns empty string for missing metadata keys', function () {
        $task = SyncTask::create(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::High,
            title: 'CVE',
            description: 'Missing metadata',
            metadata: [],
            projectId: Uuid::v7(),
        );

        expect($task->getMetadataKey())->toBe('');
    });

    it('generates unique ids', function () {
        $t1 = SyncTask::create(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::High,
            title: 'T1',
            description: 'D1',
            metadata: [],
            projectId: Uuid::v7(),
        );
        $t2 = SyncTask::create(
            type: SyncTaskType::Vulnerability,
            severity: SyncTaskSeverity::High,
            title: 'T2',
            description: 'D2',
            metadata: [],
            projectId: Uuid::v7(),
        );

        expect($t1->getId()->equals($t2->getId()))->toBeFalse();
    });
});

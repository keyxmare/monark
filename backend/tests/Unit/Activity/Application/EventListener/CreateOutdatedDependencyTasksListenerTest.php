<?php

declare(strict_types=1);

use App\Activity\Application\EventListener\CreateOutdatedDependencyTasksListener;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Shared\Domain\DTO\DependencyReadDTO;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Shared\Domain\Port\DependencyReaderPort;
use Symfony\Component\Uid\Uuid;

function stubOutdatedDepRepo(array $dependencies = []): DependencyReaderPort
{
    return new class ($dependencies) implements DependencyReaderPort {
        public function __construct(private readonly array $deps)
        {
        }
        public function findByProjectId(Uuid $projectId): array
        {
            return $this->deps;
        }
    };
}

function spySyncTaskRepo(?SyncTask $existing = null): object
{
    return new class ($existing) implements SyncTaskRepositoryInterface {
        /** @var list<SyncTask> */
        public array $saved = [];
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

describe('CreateOutdatedDependencyTasksListener', function () {
    it('creates sync tasks for outdated dependencies', function () {
        $projectId = Uuid::v7();
        $dep = new DependencyReadDTO(
            name: 'symfony/framework-bundle',
            currentVersion: '6.0.0',
            latestVersion: '7.2.0',
            packageManager: 'composer',
            isOutdated: true,
        );

        $depRepo = \stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = \spySyncTaskRepo();

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        $task = $syncTaskRepo->saved[0];
        expect($task->getType())->toBe(SyncTaskType::OutdatedDependency);
        expect($task->getSeverity())->toBe(SyncTaskSeverity::High);
        expect($task->getMetadata()['dependencyName'])->toBe('symfony/framework-bundle');
        expect($task->getMetadata()['currentVersion'])->toBe('6.0.0');
        expect($task->getMetadata()['latestVersion'])->toBe('7.2.0');
        expect($task->getMetadata()['packageManager'])->toBe('composer');
        expect($task->getTitle())->toContain('symfony/framework-bundle');
        expect($task->getDescription())->toContain('6.0.0');
        expect($task->getDescription())->toContain('7.2.0');
    });

    it('skips non-outdated dependencies', function () {
        $projectId = Uuid::v7();
        $dep = new DependencyReadDTO(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '7.2.0',
            packageManager: 'composer',
            isOutdated: false,
        );

        $depRepo = \stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = \spySyncTaskRepo();

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('updates existing open task instead of creating duplicate', function () {
        $projectId = Uuid::v7();
        $dep = new DependencyReadDTO(
            name: 'vue',
            currentVersion: '2.7.0',
            latestVersion: '3.5.0',
            packageManager: 'npm',
            isOutdated: true,
        );

        $existingTask = SyncTask::create(
            type: SyncTaskType::OutdatedDependency,
            severity: SyncTaskSeverity::Low,
            title: 'Old title',
            description: 'Old desc',
            metadata: ['dependencyName' => 'vue', 'currentVersion' => '2.6.0', 'latestVersion' => '3.4.0', 'packageManager' => 'npm'],
            projectId: $projectId,
        );

        $depRepo = \stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = \spySyncTaskRepo($existingTask);

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0])->toBe($existingTask);
        expect($existingTask->getMetadata()['currentVersion'])->toBe('2.7.0');
        expect($existingTask->getMetadata()['latestVersion'])->toBe('3.5.0');
    });

    it('assigns critical severity for 2+ major versions behind', function () {
        $projectId = Uuid::v7();
        $dep = new DependencyReadDTO(
            name: 'old-pkg',
            currentVersion: '1.0.0',
            latestVersion: '3.0.0',
            packageManager: 'composer',
            isOutdated: true,
        );

        $depRepo = \stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = \spySyncTaskRepo();

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::Critical);
    });

    it('assigns medium severity for 5+ minor versions behind', function () {
        $projectId = Uuid::v7();
        $dep = new DependencyReadDTO(
            name: 'pinia',
            currentVersion: '2.0.0',
            latestVersion: '2.5.0',
            packageManager: 'npm',
            isOutdated: true,
        );

        $depRepo = \stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = \spySyncTaskRepo();

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::Medium);
    });

    it('assigns low severity for small version gap', function () {
        $projectId = Uuid::v7();
        $dep = new DependencyReadDTO(
            name: 'lodash',
            currentVersion: '4.17.0',
            latestVersion: '4.18.0',
            packageManager: 'npm',
            isOutdated: true,
        );

        $depRepo = \stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = \spySyncTaskRepo();

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::Low);
    });
});

<?php

declare(strict_types=1);

use App\Activity\Application\EventListener\CreateOutdatedDependencyTasksListener;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Catalog\Domain\Event\ProjectScannedEvent;
use App\Catalog\Domain\Model\DetectedDependency;
use App\Catalog\Domain\Model\DetectedStack;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\ScanResult;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\DependencyType;
use App\Dependency\Domain\Model\PackageManager;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function stubOutdatedDepRepo(array $dependencies = []): DependencyRepositoryInterface
{
    return new class ($dependencies) implements DependencyRepositoryInterface {
        public function __construct(private readonly array $deps) {}
        public function findById(Uuid $id): ?Dependency { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return $this->deps; }
        public function countByProjectId(Uuid $projectId): int { return 0; }
        public function save(Dependency $dependency): void {}
        public function delete(Dependency $dependency): void {}
        public function deleteByProjectId(Uuid $projectId): void {}
    };
}

function spySyncTaskRepo(?SyncTask $existing = null): object
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

function createTestProject(): Project
{
    $provider = ProviderFactory::create();
    return Project::create(
        name: 'Test Project',
        slug: 'test-project',
        description: null,
        repositoryUrl: 'https://gitlab.example.com/test.git',
        defaultBranch: 'main',
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
        provider: $provider,
        externalId: '42',
    );
}

describe('CreateOutdatedDependencyTasksListener', function () {
    it('creates sync tasks for outdated dependencies', function () {
        $project = createTestProject();
        $dep = Dependency::create(
            name: 'symfony/framework-bundle',
            currentVersion: '6.0.0',
            latestVersion: '7.2.0',
            ltsVersion: '6.4.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            project: $project,
        );

        $depRepo = stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = spySyncTaskRepo();

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $project->getId()->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getType())->toBe(SyncTaskType::OutdatedDependency);
        expect($syncTaskRepo->saved[0]->getMetadata()['dependencyName'])->toBe('symfony/framework-bundle');
        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::High);
    });

    it('skips non-outdated dependencies', function () {
        $project = createTestProject();
        $dep = Dependency::create(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '7.2.0',
            ltsVersion: '7.2.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: false,
            project: $project,
        );

        $depRepo = stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = spySyncTaskRepo();

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $project->getId()->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('updates existing open task instead of creating duplicate', function () {
        $project = createTestProject();
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '2.7.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.0.0',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            project: $project,
        );

        $existingTask = SyncTask::create(
            type: SyncTaskType::OutdatedDependency,
            severity: SyncTaskSeverity::Low,
            title: 'Old title',
            description: 'Old desc',
            metadata: ['dependencyName' => 'vue', 'currentVersion' => '2.6.0', 'latestVersion' => '3.4.0', 'packageManager' => 'npm'],
            projectId: $project->getId(),
        );

        $depRepo = stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = spySyncTaskRepo($existingTask);

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $project->getId()->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0])->toBe($existingTask);
        expect($existingTask->getMetadata()['currentVersion'])->toBe('2.7.0');
        expect($existingTask->getMetadata()['latestVersion'])->toBe('3.5.0');
    });

    it('assigns critical severity for 2+ major versions behind', function () {
        $project = createTestProject();
        $dep = Dependency::create(
            name: 'old-pkg',
            currentVersion: '1.0.0',
            latestVersion: '3.0.0',
            ltsVersion: '3.0.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            project: $project,
        );

        $depRepo = stubOutdatedDepRepo([$dep]);
        $syncTaskRepo = spySyncTaskRepo();

        $listener = new CreateOutdatedDependencyTasksListener($depRepo, $syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $project->getId()->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::Critical);
    });
});

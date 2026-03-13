<?php

declare(strict_types=1);

use App\Activity\Application\EventListener\CreateStackUpgradeTasksListener;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Catalog\Domain\Event\ProjectScannedEvent;
use App\Catalog\Domain\Model\DetectedStack;
use App\Catalog\Domain\Model\ScanResult;
use Symfony\Component\Uid\Uuid;

function spyStackSyncTaskRepo(?SyncTask $existing = null): object
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

describe('CreateStackUpgradeTasksListener', function () {
    it('creates sync task for outdated PHP version', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: 'Symfony', version: '7.4', frameworkVersion: '5.4'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getType())->toBe(SyncTaskType::StackUpgrade);
        expect($syncTaskRepo->saved[0]->getSeverity())->toBe(SyncTaskSeverity::Medium);
        expect($syncTaskRepo->saved[0]->getMetadata()['language'])->toBe('PHP');
        expect($syncTaskRepo->saved[0]->getMetadata()['framework'])->toBe('Symfony');
        expect($syncTaskRepo->saved[0]->getMetadata()['version'])->toBe('7.4');
        expect($syncTaskRepo->saved[0]->getMetadata()['frameworkVersion'])->toBe('5.4');
        expect($syncTaskRepo->saved[0]->getTitle())->toContain('PHP');
        expect($syncTaskRepo->saved[0]->getDescription())->toContain('7.4');
    });

    it('skips current version stacks', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: 'Symfony', version: '8.4', frameworkVersion: '7.2'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('skips stacks with empty version', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: '', version: '', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('skips unknown languages', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Zig', framework: '', version: '0.11', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('creates sync task for outdated Node version', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Node', framework: 'none', version: '18.0', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getMetadata()['language'])->toBe('Node');
        expect($syncTaskRepo->saved[0]->getMetadata()['version'])->toBe('18.0');
    });

    it('skips Node at current major version (22)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Node', framework: 'none', version: '22.0', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('creates task for Node version 21 (one behind)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Node', framework: 'none', version: '21.0', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
    });

    it('creates task for outdated TypeScript version', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'TypeScript', framework: 'Vue', version: '4.9', frameworkVersion: '3.5'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getMetadata()['language'])->toBe('TypeScript');
    });

    it('skips TypeScript at current major version (5)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'TypeScript', framework: 'Vue', version: '5.7', frameworkVersion: '3.5'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('creates tasks for multiple outdated stacks', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: 'Symfony', version: '7.4', frameworkVersion: '5.4'),
                    new DetectedStack(language: 'TypeScript', framework: 'Vue', version: '4.0', frameworkVersion: '3.5'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(2);
    });

    it('updates existing task instead of creating duplicate', function () {
        $projectId = Uuid::v7();
        $existingTask = SyncTask::create(
            type: SyncTaskType::StackUpgrade,
            severity: SyncTaskSeverity::Medium,
            title: 'Old title',
            description: 'Old desc',
            metadata: ['language' => 'PHP', 'framework' => 'Symfony', 'version' => '7.3', 'frameworkVersion' => '5.4'],
            projectId: $projectId,
        );

        $syncTaskRepo = \spyStackSyncTaskRepo($existingTask);

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: 'Symfony', version: '7.4', frameworkVersion: '5.4'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0])->toBe($existingTask);
        expect($existingTask->getMetadata()['version'])->toBe('7.4');
    });
});

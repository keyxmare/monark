<?php

declare(strict_types=1);

use App\Activity\Application\EventListener\CreateStackUpgradeTasksListener;
use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use Symfony\Component\Uid\Uuid;

function spyStackSyncTaskRepo(?SyncTask $existing = null): object
{
    return new class ($existing) implements SyncTaskRepositoryInterface {
        /** @var list<SyncTask> */
        public array $saved = [];
        public ?Uuid $lastLookupProjectId = null;
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
            $this->lastLookupProjectId = $projectId;
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

describe('CreateStackUpgradeTasksListener', function () {
    it('creates sync task for outdated PHP version with exact title and description', function () {
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
        $task = $syncTaskRepo->saved[0];
        expect($task->getType())->toBe(SyncTaskType::StackUpgrade);
        expect($task->getSeverity())->toBe(SyncTaskSeverity::Medium);
        expect($task->getTitle())->toBe('Stack upgrade: PHP (Symfony)');
        expect($task->getDescription())->toBe('PHP / Symfony version 7.4 may need an upgrade.');
        expect($task->getMetadata())->toBe([
            'language' => 'PHP',
            'framework' => 'Symfony',
            'version' => '7.4',
            'frameworkVersion' => '5.4',
        ]);
    });

    it('formats title and description correctly when framework is empty', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Python', framework: '', version: '2.7', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        $task = $syncTaskRepo->saved[0];
        expect($task->getTitle())->toBe('Stack upgrade: Python ');
        expect($task->getDescription())->toBe('Python version 2.7 may need an upgrade.');
    });

    it('uses metadataKey as language:framework for duplicate lookup', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: 'Laravel', version: '7.4', frameworkVersion: '9.0'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->lastLookupType)->toBe(SyncTaskType::StackUpgrade);
        expect($syncTaskRepo->lastLookupKey)->toBe('PHP:Laravel');
        expect($syncTaskRepo->lastLookupProjectId->toRfc4122())->toBe($projectId);
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
        expect($existingTask->getTitle())->toBe('Stack upgrade: PHP (Symfony)');
        expect($existingTask->getDescription())->toBe('PHP / Symfony version 7.4 may need an upgrade.');
        expect($existingTask->getMetadata()['version'])->toBe('7.4');
        expect($existingTask->getSeverity())->toBe(SyncTaskSeverity::Medium);
    });

    it('detects outdated Python 2.x', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Python', framework: 'Django', version: '2.7', frameworkVersion: '1.11'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getMetadata()['language'])->toBe('Python');
        expect($syncTaskRepo->saved[0]->getMetadata()['framework'])->toBe('Django');
    });

    it('skips Python at current major version (3)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Python', framework: '', version: '3.12', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('detects outdated Ruby 2.x', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Ruby', framework: 'Rails', version: '2.7', frameworkVersion: '6.1'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
    });

    it('skips Ruby at current major version (3)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Ruby', framework: '', version: '3.3', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('skips Go at current major version (1)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Go', framework: '', version: '1.22', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('skips Rust at current major version (1)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Rust', framework: '', version: '1.78', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('detects outdated Java (major < 21)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Java', framework: 'Spring', version: '17.0', frameworkVersion: '3.0'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getMetadata()['language'])->toBe('Java');
    });

    it('skips Java at current major version (21)', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'Java', framework: '', version: '21.0', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('detects outdated nodejs variant', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'nodejs', framework: 'Express', version: '16.0', frameworkVersion: '4.0'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
    });

    it('handles case-insensitive language matching', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'php', framework: 'Laravel', version: '7.0', frameworkVersion: '10.0'),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
    });

    it('handles version with only major number', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: '', version: '7', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
    });

    it('does not save anything when stacks array is empty', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(0);
    });

    it('mixes outdated and current stacks correctly', function () {
        $projectId = Uuid::v7()->toRfc4122();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId,
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: '', version: '8.4', frameworkVersion: ''),
                    new DetectedStack(language: 'PHP', framework: 'Symfony', version: '7.4', frameworkVersion: '5.4'),
                    new DetectedStack(language: 'Zig', framework: '', version: '0.11', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getMetadata()['version'])->toBe('7.4');
    });

    it('uses description "unknown" when version is somehow empty but passes needsUpgrade', function () {
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

        $task = $syncTaskRepo->saved[0];
        expect($task->getDescription())->not->toContain('unknown');
        expect($task->getDescription())->toContain('7.4');
    });

    it('sets projectId on created task', function () {
        $projectId = Uuid::v7();
        $syncTaskRepo = \spyStackSyncTaskRepo();

        $listener = new CreateStackUpgradeTasksListener($syncTaskRepo);
        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(
                stacks: [
                    new DetectedStack(language: 'PHP', framework: '', version: '7.0', frameworkVersion: ''),
                ],
                dependencies: [],
            ),
        ));

        expect($syncTaskRepo->saved)->toHaveCount(1);
        expect($syncTaskRepo->saved[0]->getProjectId()->toRfc4122())->toBe($projectId->toRfc4122());
    });
});

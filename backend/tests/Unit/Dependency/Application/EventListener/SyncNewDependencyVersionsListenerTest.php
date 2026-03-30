<?php

declare(strict_types=1);

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Application\EventListener\SyncNewDependencyVersionsListener;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

function stubSyncNewVersionsRepo(array $knownPackages = []): DependencyVersionRepositoryInterface
{
    return new class ($knownPackages) implements DependencyVersionRepositoryInterface {
        public function __construct(private readonly array $knownPackages)
        {
        }
        public function findByNameAndManager(string $dependencyName, PackageManager $packageManager): array
        {
            return [];
        }
        public function findLatestByNameAndManager(string $dependencyName, PackageManager $packageManager): ?DependencyVersion
        {
            if (\in_array($dependencyName, $this->knownPackages, true)) {
                return DependencyVersion::create($dependencyName, $packageManager, '1.0.0');
            }
            return null;
        }
        public function findByNameManagerAndVersion(string $dependencyName, PackageManager $packageManager, string $version): ?DependencyVersion
        {
            return null;
        }
        public function save(DependencyVersion $version): void
        {
        }
        public function clearLatestFlag(string $dependencyName, PackageManager $packageManager): void
        {
        }
        public function flush(): void
        {
        }
    };
}

function spySyncNewVersionsBus(): object
{
    return new class () implements MessageBusInterface {
        /** @var list<object> */
        public array $dispatched = [];
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $msg = $message instanceof Envelope ? $message->getMessage() : $message;
            $this->dispatched[] = $msg;
            return Envelope::wrap($message, $stamps);
        }
    };
}

describe('SyncNewDependencyVersionsListener', function () {
    it('dispatches command for new packages', function () {
        $versionRepo = \stubSyncNewVersionsRepo([]);
        $bus = \spySyncNewVersionsBus();
        $listener = new SyncNewDependencyVersionsListener($versionRepo, $bus);

        $event = new ProjectScannedEvent(
            projectId: 'proj-1',
            scanResult: new ScanResult(
                stacks: [],
                dependencies: [
                    new DetectedDependency(
                        name: 'vue',
                        currentVersion: '3.5.0',
                        packageManager: PackageManager::Npm,
                        type: DependencyType::Runtime,
                    ),
                    new DetectedDependency(
                        name: 'symfony/http-kernel',
                        currentVersion: '7.2.0',
                        packageManager: PackageManager::Composer,
                        type: DependencyType::Runtime,
                    ),
                ],
            ),
        );

        $listener($event);

        expect($bus->dispatched)->toHaveCount(1);
        expect($bus->dispatched[0])->toBeInstanceOf(SyncDependencyVersionsCommand::class);
        expect($bus->dispatched[0]->packageNames)->toBe(['vue', 'symfony/http-kernel']);
    });

    it('skips already known packages', function () {
        $versionRepo = \stubSyncNewVersionsRepo(['vue']);
        $bus = \spySyncNewVersionsBus();
        $listener = new SyncNewDependencyVersionsListener($versionRepo, $bus);

        $event = new ProjectScannedEvent(
            projectId: 'proj-1',
            scanResult: new ScanResult(
                stacks: [],
                dependencies: [
                    new DetectedDependency(
                        name: 'vue',
                        currentVersion: '3.5.0',
                        packageManager: PackageManager::Npm,
                        type: DependencyType::Runtime,
                    ),
                    new DetectedDependency(
                        name: 'react',
                        currentVersion: '18.0.0',
                        packageManager: PackageManager::Npm,
                        type: DependencyType::Runtime,
                    ),
                ],
            ),
        );

        $listener($event);

        expect($bus->dispatched)->toHaveCount(1);
        expect($bus->dispatched[0]->packageNames)->toBe(['react']);
    });

    it('does nothing when all packages are known', function () {
        $versionRepo = \stubSyncNewVersionsRepo(['vue', 'react']);
        $bus = \spySyncNewVersionsBus();
        $listener = new SyncNewDependencyVersionsListener($versionRepo, $bus);

        $event = new ProjectScannedEvent(
            projectId: 'proj-1',
            scanResult: new ScanResult(
                stacks: [],
                dependencies: [
                    new DetectedDependency(
                        name: 'vue',
                        currentVersion: '3.5.0',
                        packageManager: PackageManager::Npm,
                        type: DependencyType::Runtime,
                    ),
                    new DetectedDependency(
                        name: 'react',
                        currentVersion: '18.0.0',
                        packageManager: PackageManager::Npm,
                        type: DependencyType::Runtime,
                    ),
                ],
            ),
        );

        $listener($event);

        expect($bus->dispatched)->toBeEmpty();
    });
});

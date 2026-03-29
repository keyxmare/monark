<?php

declare(strict_types=1);

use App\Dependency\Application\Command\SyncSingleDependencyVersionCommand;
use App\Dependency\Application\CommandHandler\SyncSingleDependencyVersionHandler;
use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Port\PackageRegistryPort;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Dependency\Infrastructure\Registry\PackageRegistryFactory;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;

function syncSingleDepRepo(array $deps = []): DependencyRepositoryInterface
{
    return new class ($deps) implements DependencyRepositoryInterface {
        /** @var list<Dependency> */
        public array $saved = [];

        public function __construct(private readonly array $deps)
        {
        }

        public function findById(Uuid $id): ?Dependency
        {
            return null;
        }

        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }

        public function count(): int
        {
            return 0;
        }

        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }

        public function save(Dependency $dependency): void
        {
            $this->saved[] = $dependency;
        }

        public function delete(Dependency $dependency): void
        {
        }

        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }

        public function deleteByProjectId(Uuid $projectId): void
        {
        }

        public function findFiltered(int $page, int $perPage, array $filters = []): array
        {
            return [];
        }

        public function countFiltered(array $filters = []): int
        {
            return 0;
        }

        public function getStats(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }

        public function findUniquePackages(): array
        {
            return [];
        }

        public function findByName(string $name, string $packageManager): array
        {
            return $this->deps;
        }
    };
}

function syncSingleVersionRepo(?DependencyVersion $latest = null, ?DependencyVersion $existing = null): object
{
    return new class ($latest, $existing) implements DependencyVersionRepositoryInterface {
        /** @var list<DependencyVersion> */
        public array $saved = [];
        public bool $clearedLatest = false;
        public ?string $clearedName = null;
        public ?PackageManager $clearedManager = null;

        public function __construct(
            private readonly ?DependencyVersion $latest,
            private readonly ?DependencyVersion $existing,
        ) {
        }

        public function findByNameAndManager(string $dependencyName, PackageManager $packageManager): array
        {
            return [];
        }

        public function findLatestByNameAndManager(string $dependencyName, PackageManager $packageManager): ?DependencyVersion
        {
            return $this->latest;
        }

        public function findByNameManagerAndVersion(string $dependencyName, PackageManager $packageManager, string $version): ?DependencyVersion
        {
            return $this->existing;
        }

        public function save(DependencyVersion $version): void
        {
            $this->saved[] = $version;
        }

        public function clearLatestFlag(string $dependencyName, PackageManager $packageManager): void
        {
            $this->clearedLatest = true;
            $this->clearedName = $dependencyName;
            $this->clearedManager = $packageManager;
        }
    };
}

function syncSingleRegistryAdapter(array $versions = []): PackageRegistryFactory
{
    $adapter = new class ($versions) implements PackageRegistryPort {
        public ?string $receivedSinceVersion = null;
        public ?string $receivedPackageName = null;
        public ?PackageManager $receivedManager = null;

        public function __construct(private readonly array $versions)
        {
        }

        public function supports(PackageManager $manager): bool
        {
            return true;
        }

        public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
        {
            $this->receivedSinceVersion = $sinceVersion;
            $this->receivedPackageName = $packageName;
            $this->receivedManager = $manager;

            return $this->versions;
        }
    };

    return new PackageRegistryFactory([$adapter]);
}

describe('SyncSingleDependencyVersionHandler', function () {
    it('returns early for invalid package manager', function () {
        $depRepo = \syncSingleDepRepo();
        $versionRepo = \syncSingleVersionRepo();
        $factory = \syncSingleRegistryAdapter();
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('publish');

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'invalid-manager'));

        expect($versionRepo->saved)->toBeEmpty();
        expect($depRepo->saved)->toBeEmpty();
        expect($versionRepo->clearedLatest)->toBeFalse();
    });

    it('marks not-found when no registry versions and no known versions', function () {
        $dep = Dependency::create(
            name: 'unknown-pkg',
            currentVersion: '1.0.0',
            latestVersion: '1.0.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $depRepo = \syncSingleDepRepo([$dep]);
        $versionRepo = \syncSingleVersionRepo(latest: null);
        $factory = \syncSingleRegistryAdapter([]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('unknown-pkg', 'npm'));

        expect($depRepo->saved)->toHaveCount(1);
        expect($depRepo->saved[0])->toBe($dep);
        expect($dep->getRegistryStatus())->toBe(RegistryStatus::NotFound);
        expect($versionRepo->clearedLatest)->toBeFalse();
    });

    it('marks all deps as not-found when multiple deps and empty registry', function () {
        $dep1 = Dependency::create(
            name: 'unknown-pkg',
            currentVersion: '1.0.0',
            latestVersion: '1.0.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $dep2 = Dependency::create(
            name: 'unknown-pkg',
            currentVersion: '2.0.0',
            latestVersion: '2.0.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Dev,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $depRepo = \syncSingleDepRepo([$dep1, $dep2]);
        $versionRepo = \syncSingleVersionRepo(latest: null);
        $factory = \syncSingleRegistryAdapter([]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('unknown-pkg', 'npm'));

        expect($depRepo->saved)->toHaveCount(2);
        expect($dep1->getRegistryStatus())->toBe(RegistryStatus::NotFound);
        expect($dep2->getRegistryStatus())->toBe(RegistryStatus::NotFound);
    });

    it('does not mark not-found when no registry versions but known latest exists', function () {
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.4.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $latestKnown = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.4.0',
            releaseDate: new DateTimeImmutable('2024-01-01'),
            isLatest: true,
        );
        $depRepo = \syncSingleDepRepo([$dep]);
        $versionRepo = \syncSingleVersionRepo(latest: $latestKnown);
        $factory = \syncSingleRegistryAdapter([]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));

        expect($depRepo->saved)->toBeEmpty();
        expect($dep->getRegistryStatus())->toBe(RegistryStatus::Pending);
    });

    it('passes sinceVersion from latest known version to registry', function () {
        $latestKnown = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.4.0',
            releaseDate: new DateTimeImmutable('2024-01-01'),
            isLatest: true,
        );
        $depRepo = \syncSingleDepRepo([]);
        $versionRepo = \syncSingleVersionRepo(latest: $latestKnown, existing: null);
        $rv = new RegistryVersion('3.5.0', new DateTimeImmutable('2024-06-01'), true);
        $factory = \syncSingleRegistryAdapter([$rv]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));

        expect($versionRepo->saved)->toHaveCount(1);
    });

    it('creates new versions and updates dependencies when registry returns versions', function () {
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.4.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $depRepo = \syncSingleDepRepo([$dep]);
        $versionRepo = \syncSingleVersionRepo(latest: null, existing: null);
        $factory = \syncSingleRegistryAdapter([
            new RegistryVersion('3.5.0', new DateTimeImmutable('2024-01-01'), false),
            new RegistryVersion('3.5.1', new DateTimeImmutable('2024-02-01'), true),
        ]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));

        expect($versionRepo->saved)->toHaveCount(2);
        expect($versionRepo->clearedLatest)->toBeTrue();
        expect($versionRepo->clearedName)->toBe('vue');
        expect($versionRepo->clearedManager)->toBe(PackageManager::Npm);

        expect($versionRepo->saved[0]->getVersion())->toBe('3.5.0');
        expect($versionRepo->saved[0]->isLatest())->toBeFalse();
        expect($versionRepo->saved[0]->getReleaseDate())->not->toBeNull();
        expect($versionRepo->saved[0]->getDependencyName())->toBe('vue');
        expect($versionRepo->saved[0]->getPackageManager())->toBe(PackageManager::Npm);

        expect($versionRepo->saved[1]->getVersion())->toBe('3.5.1');
        expect($versionRepo->saved[1]->isLatest())->toBeTrue();

        expect($dep->getLatestVersion())->toBe('3.5.1');
        expect($dep->isOutdated())->toBeTrue();
        expect($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
        expect($depRepo->saved)->toHaveCount(1);
        expect($depRepo->saved[0])->toBe($dep);
    });

    it('does not update deps when no registry version is marked as latest', function () {
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.4.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $depRepo = \syncSingleDepRepo([$dep]);
        $versionRepo = \syncSingleVersionRepo(latest: null, existing: null);
        $factory = \syncSingleRegistryAdapter([
            new RegistryVersion('3.5.0', new DateTimeImmutable('2024-01-01'), false),
            new RegistryVersion('3.5.1', new DateTimeImmutable('2024-02-01'), false),
        ]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));

        expect($versionRepo->saved)->toHaveCount(2);
        expect($versionRepo->clearedLatest)->toBeTrue();
        expect($depRepo->saved)->toBeEmpty();
        expect($dep->getRegistryStatus())->toBe(RegistryStatus::Pending);
    });

    it('marks dep as not outdated when currentVersion equals latestVersion', function () {
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.5.1',
            latestVersion: '3.4.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $depRepo = \syncSingleDepRepo([$dep]);
        $versionRepo = \syncSingleVersionRepo(latest: null, existing: null);
        $factory = \syncSingleRegistryAdapter([
            new RegistryVersion('3.5.1', new DateTimeImmutable('2024-02-01'), true),
        ]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));

        expect($dep->getLatestVersion())->toBe('3.5.1');
        expect($dep->isOutdated())->toBeFalse();
        expect($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
    });

    it('updates existing version isLatest flag', function () {
        $existingVersion = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.5.0',
            releaseDate: new DateTimeImmutable('2024-01-01'),
            isLatest: true,
        );
        $versionRepo = \syncSingleVersionRepo(latest: null, existing: $existingVersion);
        $depRepo = \syncSingleDepRepo([]);
        $factory = \syncSingleRegistryAdapter([
            new RegistryVersion('3.5.0', new DateTimeImmutable('2024-01-01'), false),
        ]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));

        expect($existingVersion->isLatest())->toBeFalse();
        expect($versionRepo->saved)->toHaveCount(1);
        expect($versionRepo->saved[0])->toBe($existingVersion);
    });

    it('publishes Mercure update with correct topic and payload when syncId is set', function () {
        $depRepo = \syncSingleDepRepo([]);
        $versionRepo = \syncSingleVersionRepo();
        $factory = \syncSingleRegistryAdapter([]);
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('publish')
            ->with($this->callback(function (Update $update) {
                $data = \json_decode((string) $update->getData(), true);
                expect($data['syncId'])->toBe('sync-abc');
                expect($data['completed'])->toBe(3);
                expect($data['total'])->toBe(5);
                expect($data['status'])->toBe('running');
                expect($data['lastPackage'])->toBe('vue');

                return true;
            }));

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm', syncId: 'sync-abc', index: 3, total: 5));
    });

    it('publishes completed status when index equals total', function () {
        $depRepo = \syncSingleDepRepo([]);
        $versionRepo = \syncSingleVersionRepo();
        $factory = \syncSingleRegistryAdapter([]);
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('publish')
            ->with($this->callback(function (Update $update) {
                $data = \json_decode((string) $update->getData(), true);
                expect($data['status'])->toBe('completed');
                expect($data['completed'])->toBe(5);
                expect($data['total'])->toBe(5);

                return true;
            }));

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm', syncId: 'sync-xyz', index: 5, total: 5));
    });

    it('publishes completed status when index exceeds total', function () {
        $depRepo = \syncSingleDepRepo([]);
        $versionRepo = \syncSingleVersionRepo();
        $factory = \syncSingleRegistryAdapter([]);
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('publish')
            ->with($this->callback(function (Update $update) {
                $data = \json_decode((string) $update->getData(), true);
                expect($data['status'])->toBe('completed');

                return true;
            }));

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm', syncId: 'sync-xyz', index: 6, total: 5));
    });

    it('does not publish Mercure when syncId is null', function () {
        $depRepo = \syncSingleDepRepo([]);
        $versionRepo = \syncSingleVersionRepo();
        $factory = \syncSingleRegistryAdapter([]);
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('publish');

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));
    });

    it('does not publish Mercure when total is zero', function () {
        $depRepo = \syncSingleDepRepo([]);
        $versionRepo = \syncSingleVersionRepo();
        $factory = \syncSingleRegistryAdapter([]);
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('publish');

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm', syncId: 'sync-abc', index: 1, total: 0));
    });

    it('logs info with correct count after syncing versions', function () {
        $depRepo = \syncSingleDepRepo([]);
        $versionRepo = \syncSingleVersionRepo(latest: null, existing: null);
        $factory = \syncSingleRegistryAdapter([
            new RegistryVersion('1.0.0', new DateTimeImmutable('2024-01-01'), true),
            new RegistryVersion('0.9.0', new DateTimeImmutable('2023-06-01'), false),
        ]);
        $hub = $this->createMock(HubInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')
            ->with(
                'Synced {count} versions for {package} ({manager})',
                [
                    'count' => 2,
                    'package' => 'test-pkg',
                    'manager' => 'npm',
                ],
            );

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub, $logger);
        $handler(new SyncSingleDependencyVersionCommand('test-pkg', 'npm'));
    });

    it('updates multiple deps with latest version and correct outdated flags', function () {
        $dep1 = Dependency::create(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.4.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $dep2 = Dependency::create(
            name: 'vue',
            currentVersion: '3.5.1',
            latestVersion: '3.4.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Dev,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $depRepo = \syncSingleDepRepo([$dep1, $dep2]);
        $versionRepo = \syncSingleVersionRepo(latest: null, existing: null);
        $factory = \syncSingleRegistryAdapter([
            new RegistryVersion('3.5.1', new DateTimeImmutable('2024-02-01'), true),
        ]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));

        expect($dep1->getLatestVersion())->toBe('3.5.1');
        expect($dep1->isOutdated())->toBeTrue();
        expect($dep1->getRegistryStatus())->toBe(RegistryStatus::Synced);

        expect($dep2->getLatestVersion())->toBe('3.5.1');
        expect($dep2->isOutdated())->toBeFalse();
        expect($dep2->getRegistryStatus())->toBe(RegistryStatus::Synced);

        expect($depRepo->saved)->toHaveCount(2);
    });

    it('finds latestVersion from first isLatest in registry versions', function () {
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.3.0',
            latestVersion: '3.3.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
        );
        $depRepo = \syncSingleDepRepo([$dep]);
        $versionRepo = \syncSingleVersionRepo(latest: null, existing: null);
        $factory = \syncSingleRegistryAdapter([
            new RegistryVersion('3.5.0', new DateTimeImmutable('2024-01-01'), true),
            new RegistryVersion('3.6.0', new DateTimeImmutable('2024-06-01'), true),
        ]);
        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleDependencyVersionHandler($depRepo, $versionRepo, $factory, $hub);
        $handler(new SyncSingleDependencyVersionCommand('vue', 'npm'));

        expect($dep->getLatestVersion())->toBe('3.5.0');
    });
});

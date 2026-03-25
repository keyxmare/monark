<?php

declare(strict_types=1);

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Application\CommandHandler\SyncDependencyVersionsHandler;
use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Dependency\Infrastructure\Registry\PackageRegistryFactory;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function stubSyncDepRepo(): DependencyRepositoryInterface&stdClass
{
    return new class () extends stdClass implements DependencyRepositoryInterface {
        /** @var list<Dependency> */
        public array $saved = [];

        public function findById(Uuid $id): ?Dependency { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
        public function save(Dependency $dependency): void { $this->saved[] = $dependency; }
        public function delete(Dependency $dependency): void {}
        public function countByProjectId(Uuid $projectId): int { return 0; }
        public function deleteByProjectId(Uuid $projectId): void {}
        public function findFiltered(int $page, int $perPage, array $filters = []): array { return []; }
        public function countFiltered(array $filters = []): int { return 0; }
        public function getStats(array $filters = []): array { return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0]; }

        public function findUniquePackages(): array
        {
            return [
                ['name' => 'vue', 'packageManager' => 'npm'],
            ];
        }

        public function findByName(string $name, string $packageManager): array
        {
            if ($name === 'vue') {
                return [Dependency::create(
                    name: 'vue',
                    currentVersion: '3.5.0',
                    latestVersion: '3.5.0',
                    ltsVersion: '',
                    packageManager: PackageManager::Npm,
                    type: DependencyType::Runtime,
                    isOutdated: false,
                    projectId: Uuid::v7(),
                )];
            }
            return [];
        }
    };
}

function stubSyncVersionRepo(): DependencyVersionRepositoryInterface&stdClass
{
    return new class () extends stdClass implements DependencyVersionRepositoryInterface {
        /** @var list<DependencyVersion> */
        public array $saved = [];
        public bool $clearedLatest = false;

        public function findByNameAndManager(string $dependencyName, PackageManager $packageManager): array { return []; }
        public function findLatestByNameAndManager(string $dependencyName, PackageManager $packageManager): ?DependencyVersion { return null; }
        public function findByNameManagerAndVersion(string $dependencyName, PackageManager $packageManager, string $version): ?DependencyVersion { return null; }
        public function save(DependencyVersion $version): void { $this->saved[] = $version; }
        public function clearLatestFlag(string $dependencyName, PackageManager $packageManager): void { $this->clearedLatest = true; }
    };
}

function stubSyncRegistryFactory(array $versions): PackageRegistryFactory
{
    return new class ($versions) extends PackageRegistryFactory {
        public function __construct(private readonly array $versions)
        {
            parent::__construct([]);
        }

        public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
        {
            return $this->versions;
        }
    };
}

describe('SyncDependencyVersionsHandler', function () {
    it('syncs versions from registry and updates dependencies', function () {
        $depRepo = \stubSyncDepRepo();
        $versionRepo = \stubSyncVersionRepo();
        $factory = \stubSyncRegistryFactory([
            new RegistryVersion('3.5.0', new \DateTimeImmutable('2025-09-01'), false),
            new RegistryVersion('3.5.13', new \DateTimeImmutable('2026-02-15'), true),
        ]);

        $handler = new SyncDependencyVersionsHandler($depRepo, $versionRepo, $factory);
        $synced = $handler(new SyncDependencyVersionsCommand());

        expect($synced)->toBe(1);
        expect($versionRepo->saved)->toHaveCount(2);
        expect($versionRepo->saved[0]->getVersion())->toBe('3.5.0');
        expect($versionRepo->saved[1]->getVersion())->toBe('3.5.13');
        expect($versionRepo->saved[1]->isLatest())->toBeTrue();
        expect($versionRepo->clearedLatest)->toBeTrue();

        expect($depRepo->saved)->toHaveCount(1);
        expect($depRepo->saved[0]->getLatestVersion())->toBe('3.5.13');
        expect($depRepo->saved[0]->isOutdated())->toBeTrue();
    });

    it('returns 0 when no packages to sync', function () {
        $depRepo = new class () extends stdClass implements DependencyRepositoryInterface {
            public function findById(Uuid $id): ?Dependency { return null; }
            public function findAll(int $page = 1, int $perPage = 20): array { return []; }
            public function count(): int { return 0; }
            public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
            public function save(Dependency $dependency): void {}
            public function delete(Dependency $dependency): void {}
            public function countByProjectId(Uuid $projectId): int { return 0; }
            public function deleteByProjectId(Uuid $projectId): void {}
            public function findFiltered(int $page, int $perPage, array $filters = []): array { return []; }
            public function countFiltered(array $filters = []): int { return 0; }
            public function findUniquePackages(): array { return []; }
            public function findByName(string $name, string $packageManager): array { return []; }
            public function getStats(array $filters = []): array { return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0]; }
        };

        $versionRepo = \stubSyncVersionRepo();
        $factory = \stubSyncRegistryFactory([]);

        $handler = new SyncDependencyVersionsHandler($depRepo, $versionRepo, $factory);
        $synced = $handler(new SyncDependencyVersionsCommand());

        expect($synced)->toBe(0);
    });
});

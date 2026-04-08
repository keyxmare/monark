<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Infrastructure\Adapter\DependencyReaderAdapter;
use App\Shared\Domain\DTO\DependencyReadDTO;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function stubDepReaderRepo(array $dependencies = []): DependencyRepositoryInterface
{
    return new class ($dependencies) implements DependencyRepositoryInterface {
        public ?Uuid $receivedProjectId = null;
        public ?int $receivedPage = null;
        public ?int $receivedPerPage = null;

        public function __construct(private readonly array $dependencies)
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

        public function save(Dependency $dependency): void
        {
        }

        public function delete(Dependency $dependency): void
        {
        }

        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            $this->receivedProjectId = $projectId;
            $this->receivedPage = $page;
            $this->receivedPerPage = $perPage;

            return $this->dependencies;
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

        public function findUniquePackages(): array
        {
            return [];
        }

        public function findByName(string $name, string $packageManager): array
        {
            return [];
        }

        public function findByNameManagerAndProjectId(string $name, string $packageManager, Uuid $projectId): ?Dependency
        {
            return null;
        }
        public function getStats(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }

        public function getStatsSingle(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }

        public function findFilteredWithVersionDates(int $page, int $perPage, array $filters = []): array
        {
            return [];
        }
    };
}

describe('DependencyReaderAdapter', function () {
    it('returns empty array when no dependencies exist', function () {
        $adapter = new DependencyReaderAdapter(\stubDepReaderRepo([]));
        $result = $adapter->findByProjectId(Uuid::v7());

        expect($result)->toBeEmpty();
        expect($result)->toBeArray();
    });

    it('calls findByProjectId with page 1 and perPage 1000', function () {
        $repo = \stubDepReaderRepo([]);
        $adapter = new DependencyReaderAdapter($repo);
        $projectId = Uuid::v7();

        $adapter->findByProjectId($projectId);

        expect($repo->receivedProjectId->toRfc4122())->toBe($projectId->toRfc4122());
        expect($repo->receivedPage)->toBe(1);
        expect($repo->receivedPerPage)->toBe(1000);
    });

    it('maps dependency fields to DTO correctly', function () {
        $projectId = Uuid::v7();
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '3.6.0',
            ltsVersion: '3.4.0',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
        );

        $adapter = new DependencyReaderAdapter(\stubDepReaderRepo([$dep]));
        $result = $adapter->findByProjectId($projectId);

        expect($result)->toHaveCount(1);
        expect($result[0])->toBeInstanceOf(DependencyReadDTO::class);
        expect($result[0]->name)->toBe('vue');
        expect($result[0]->currentVersion)->toBe('3.5.0');
        expect($result[0]->latestVersion)->toBe('3.6.0');
        expect($result[0]->packageManager)->toBe('npm');
        expect($result[0]->isOutdated)->toBeTrue();
        expect($result[0]->vulnerabilities)->toBeEmpty();
        expect($result[0]->vulnerabilities)->toBeArray();
    });

    it('maps composer runtime dependency correctly', function () {
        $projectId = Uuid::v7();
        $dep = Dependency::create(
            name: 'symfony/http-kernel',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
        );

        $adapter = new DependencyReaderAdapter(\stubDepReaderRepo([$dep]));
        $result = $adapter->findByProjectId($projectId);

        expect($result[0]->name)->toBe('symfony/http-kernel');
        expect($result[0]->currentVersion)->toBe('7.2.0');
        expect($result[0]->latestVersion)->toBe('8.0.0');
        expect($result[0]->packageManager)->toBe('composer');
        expect($result[0]->isOutdated)->toBeTrue();
    });

    it('maps non-outdated dependency correctly', function () {
        $projectId = Uuid::v7();
        $dep = Dependency::create(
            name: 'lodash',
            currentVersion: '4.17.21',
            latestVersion: '4.17.21',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Dev,
            isOutdated: false,
            projectId: $projectId,
        );

        $adapter = new DependencyReaderAdapter(\stubDepReaderRepo([$dep]));
        $result = $adapter->findByProjectId($projectId);

        expect($result[0]->isOutdated)->toBeFalse();
        expect($result[0]->currentVersion)->toBe('4.17.21');
        expect($result[0]->latestVersion)->toBe('4.17.21');
    });

    it('maps multiple dependencies preserving order', function () {
        $projectId = Uuid::v7();
        $dep1 = Dependency::create(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '3.6.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
        );
        $dep2 = Dependency::create(
            name: 'react',
            currentVersion: '18.0.0',
            latestVersion: '18.2.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
        );

        $adapter = new DependencyReaderAdapter(\stubDepReaderRepo([$dep1, $dep2]));
        $result = $adapter->findByProjectId($projectId);

        expect($result)->toHaveCount(2);
        expect($result[0]->name)->toBe('vue');
        expect($result[1]->name)->toBe('react');
    });
});

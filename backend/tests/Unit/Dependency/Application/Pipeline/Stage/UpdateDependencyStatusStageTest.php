<?php

declare(strict_types=1);

use App\Dependency\Application\Pipeline\Stage\UpdateDependencyStatusStage;
use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function makeUpdateStatusDepRepo(array $deps): DependencyRepositoryInterface
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

        public function getStatsSingle(array $filters = []): array
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

        public function findByNameManagerAndProjectId(string $name, string $packageManager, Uuid $projectId): ?Dependency
        {
            return null;
        }

        public function findFilteredWithVersionDates(int $page, int $perPage, array $filters = []): array
        {
            return [];
        }
    };
}

function makeDepForStatus(string $currentVersion): Dependency
{
    return Dependency::create(
        name: 'vue',
        currentVersion: $currentVersion,
        latestVersion: $currentVersion,
        ltsVersion: '',
        packageManager: PackageManager::Npm,
        type: DependencyType::Runtime,
        isOutdated: false,
        projectId: Uuid::v7(),
    );
}

describe('UpdateDependencyStatusStage', function () {
    it('marks deps as not found when no registry versions and no latest', function () {
        $dep = \makeDepForStatus('1.0.0');
        $repo = \makeUpdateStatusDepRepo([$dep]);
        $stage = new UpdateDependencyStatusStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm);

        $stage($ctx);

        expect($dep->getRegistryStatus())->toBe(RegistryStatus::NotFound)
            ->and($repo->saved)->toHaveCount(1);
    });

    it('updates dep latestVersion and isOutdated when latestVersion resolved', function () {
        $dep = \makeDepForStatus('1.0.0');
        $repo = \makeUpdateStatusDepRepo([$dep]);
        $stage = new UpdateDependencyStatusStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withLatestVersion('2.0.0');

        $stage($ctx);

        expect($dep->getLatestVersion())->toBe('2.0.0')
            ->and($dep->isOutdated())->toBeTrue()
            ->and($dep->getRegistryStatus())->toBe(RegistryStatus::Synced)
            ->and($repo->saved)->toHaveCount(1);
    });

    it('marks dep as not outdated when current equals latest', function () {
        $dep = \makeDepForStatus('2.0.0');
        $repo = \makeUpdateStatusDepRepo([$dep]);
        $stage = new UpdateDependencyStatusStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withLatestVersion('2.0.0');

        $stage($ctx);

        expect($dep->isOutdated())->toBeFalse()
            ->and($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
    });

    it('does nothing when no deps returned for package', function () {
        $repo = \makeUpdateStatusDepRepo([]);
        $stage = new UpdateDependencyStatusStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm);

        $stage($ctx);

        expect($repo->saved)->toBeEmpty();
    });
});

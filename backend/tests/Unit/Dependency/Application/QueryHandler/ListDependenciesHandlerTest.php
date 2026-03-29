<?php

declare(strict_types=1);

use App\Dependency\Application\DTO\DependencyListOutput;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Application\Query\ListDependenciesQuery;
use App\Dependency\Application\QueryHandler\ListDependenciesHandler;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function stubListDepsVersionRepo(array $versionMap = []): DependencyVersionRepositoryInterface
{
    return new class ($versionMap) implements DependencyVersionRepositoryInterface {
        public function __construct(private readonly array $versionMap)
        {
        }

        public function findByNameAndManager(string $dependencyName, PackageManager $packageManager): array
        {
            return [];
        }

        public function findLatestByNameAndManager(string $dependencyName, PackageManager $packageManager): ?DependencyVersion
        {
            return null;
        }

        public function findByNameManagerAndVersion(string $dependencyName, PackageManager $packageManager, string $version): ?DependencyVersion
        {
            $key = $dependencyName . '|' . $packageManager->value . '|' . $version;

            return $this->versionMap[$key] ?? null;
        }

        public function save(DependencyVersion $version): void
        {
        }

        public function clearLatestFlag(string $dependencyName, PackageManager $packageManager): void
        {
        }
    };
}

function stubListDepsRepo(array $dependencies = [], int $count = 0, ?array $receivedFilters = null): object
{
    return new class ($dependencies, $count) implements DependencyRepositoryInterface {
        public ?array $receivedFilters = null;
        public ?int $receivedPage = null;
        public ?int $receivedPerPage = null;

        public function __construct(private readonly array $dependencies, private readonly int $count)
        {
        }

        public function findById(Uuid $id): ?Dependency
        {
            return null;
        }

        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->dependencies;
        }

        public function count(): int
        {
            return $this->count;
        }

        public function save(Dependency $dependency): void
        {
        }

        public function delete(Dependency $dependency): void
        {
        }

        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
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
            $this->receivedPage = $page;
            $this->receivedPerPage = $perPage;
            $this->receivedFilters = $filters;

            return $this->dependencies;
        }

        public function countFiltered(array $filters = []): int
        {
            return $this->count;
        }

        public function findUniquePackages(): array
        {
            return [];
        }

        public function findByName(string $name, string $packageManager): array
        {
            return [];
        }

        public function getStats(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }
    };
}

describe('ListDependenciesHandler', function () {
    it('returns paginated dependencies with correct field values', function () {
        $projectId = Uuid::v7();
        $dep1 = Dependency::create(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
        );
        $dep2 = Dependency::create(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.4.0',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
        );

        $handler = new ListDependenciesHandler(stubListDepsRepo([$dep1, $dep2], 2), stubListDepsVersionRepo());
        $result = $handler(new ListDependenciesQuery(1, 20));

        expect($result)->toBeInstanceOf(DependencyListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
        expect($result->pagination->page)->toBe(1);
        expect($result->pagination->perPage)->toBe(20);

        $item0 = $result->pagination->items[0];
        expect($item0)->toBeInstanceOf(DependencyOutput::class);
        expect($item0->name)->toBe('symfony/framework-bundle');
        expect($item0->currentVersion)->toBe('7.2.0');
        expect($item0->latestVersion)->toBe('8.0.0');
        expect($item0->ltsVersion)->toBe('7.4.0');
        expect($item0->packageManager)->toBe('composer');
        expect($item0->type)->toBe('runtime');
        expect($item0->isOutdated)->toBeTrue();
        expect($item0->currentVersionReleasedAt)->toBeNull();
        expect($item0->latestVersionReleasedAt)->toBeNull();

        $item1 = $result->pagination->items[1];
        expect($item1->name)->toBe('vue');
        expect($item1->currentVersion)->toBe('3.4.0');
        expect($item1->latestVersion)->toBe('3.5.0');
        expect($item1->packageManager)->toBe('npm');
    });

    it('returns empty list when no dependencies', function () {
        $handler = new ListDependenciesHandler(stubListDepsRepo([], 0), stubListDepsVersionRepo());
        $result = $handler(new ListDependenciesQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
        expect($result->pagination->page)->toBe(1);
        expect($result->pagination->perPage)->toBe(20);
    });

    it('passes page and perPage to repository', function () {
        $repo = stubListDepsRepo([], 0);
        $handler = new ListDependenciesHandler($repo, stubListDepsVersionRepo());
        $handler(new ListDependenciesQuery(3, 50));

        expect($repo->receivedPage)->toBe(3);
        expect($repo->receivedPerPage)->toBe(50);
    });

    it('builds filters from query parameters excluding null and empty', function () {
        $repo = stubListDepsRepo([], 0);
        $handler = new ListDependenciesHandler($repo, stubListDepsVersionRepo());
        $handler(new ListDependenciesQuery(
            page: 1,
            perPage: 20,
            projectId: 'proj-123',
            search: 'vue',
            packageManager: 'npm',
            type: 'runtime',
            sort: 'name',
            sortDir: 'desc',
        ));

        expect($repo->receivedFilters)->toBe([
            'projectId' => 'proj-123',
            'search' => 'vue',
            'packageManager' => 'npm',
            'type' => 'runtime',
            'sort' => 'name',
            'sortDir' => 'desc',
        ]);
    });

    it('excludes null filters but keeps sort defaults', function () {
        $repo = stubListDepsRepo([], 0);
        $handler = new ListDependenciesHandler($repo, stubListDepsVersionRepo());
        $handler(new ListDependenciesQuery(
            page: 1,
            perPage: 20,
            projectId: null,
            search: null,
            packageManager: null,
            type: null,
        ));

        expect($repo->receivedFilters)->toHaveKey('sort');
        expect($repo->receivedFilters)->toHaveKey('sortDir');
        expect($repo->receivedFilters)->not->toHaveKey('projectId');
        expect($repo->receivedFilters)->not->toHaveKey('search');
        expect($repo->receivedFilters)->not->toHaveKey('packageManager');
        expect($repo->receivedFilters)->not->toHaveKey('type');
    });

    it('adds isOutdated to filters when set to true', function () {
        $repo = stubListDepsRepo([], 0);
        $handler = new ListDependenciesHandler($repo, stubListDepsVersionRepo());
        $handler(new ListDependenciesQuery(isOutdated: true));

        expect($repo->receivedFilters)->toHaveKey('isOutdated');
        expect($repo->receivedFilters['isOutdated'])->toBeTrue();
    });

    it('adds isOutdated to filters when set to false', function () {
        $repo = stubListDepsRepo([], 0);
        $handler = new ListDependenciesHandler($repo, stubListDepsVersionRepo());
        $handler(new ListDependenciesQuery(isOutdated: false));

        expect($repo->receivedFilters)->toHaveKey('isOutdated');
        expect($repo->receivedFilters['isOutdated'])->toBeFalse();
    });

    it('does not add isOutdated to filters when null', function () {
        $repo = stubListDepsRepo([], 0);
        $handler = new ListDependenciesHandler($repo, stubListDepsVersionRepo());
        $handler(new ListDependenciesQuery(isOutdated: null));

        expect($repo->receivedFilters)->not->toHaveKey('isOutdated');
    });

    it('populates release dates from version repository', function () {
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.5.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: Uuid::v7(),
        );

        $currentVer = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.4.0',
            releaseDate: new DateTimeImmutable('2024-06-15T10:00:00+00:00'),
        );
        $latestVer = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.5.0',
            releaseDate: new DateTimeImmutable('2025-01-20T14:30:00+00:00'),
        );

        $versionRepo = stubListDepsVersionRepo([
            'vue|npm|3.4.0' => $currentVer,
            'vue|npm|3.5.0' => $latestVer,
        ]);

        $handler = new ListDependenciesHandler(stubListDepsRepo([$dep], 1), $versionRepo);
        $result = $handler(new ListDependenciesQuery());

        $item = $result->pagination->items[0];
        expect($item->currentVersionReleasedAt)->toBe('2024-06-15T10:00:00+00:00');
        expect($item->latestVersionReleasedAt)->toBe('2025-01-20T14:30:00+00:00');
    });

    it('returns null release dates when version has no release date', function () {
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.5.0',
            ltsVersion: '',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: Uuid::v7(),
        );

        $currentVer = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.4.0',
            releaseDate: null,
        );

        $versionRepo = stubListDepsVersionRepo([
            'vue|npm|3.4.0' => $currentVer,
        ]);

        $handler = new ListDependenciesHandler(stubListDepsRepo([$dep], 1), $versionRepo);
        $result = $handler(new ListDependenciesQuery());

        $item = $result->pagination->items[0];
        expect($item->currentVersionReleasedAt)->toBeNull();
        expect($item->latestVersionReleasedAt)->toBeNull();
    });

    it('excludes empty string filters', function () {
        $repo = stubListDepsRepo([], 0);
        $handler = new ListDependenciesHandler($repo, stubListDepsVersionRepo());
        $handler(new ListDependenciesQuery(
            projectId: '',
            search: '',
            packageManager: '',
            type: '',
        ));

        expect($repo->receivedFilters)->not->toHaveKey('projectId');
        expect($repo->receivedFilters)->not->toHaveKey('search');
        expect($repo->receivedFilters)->not->toHaveKey('packageManager');
        expect($repo->receivedFilters)->not->toHaveKey('type');
    });
});

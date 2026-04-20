<?php

declare(strict_types=1);

use App\Hub\Shared\Application\DTO\ProjectActivitySummary;
use App\Hub\Shared\Domain\Port\ProjectActivityInterface;
use App\Hub\Shared\Domain\Port\ProjectCoverageInterface;
use App\Monitoring\Catalog\Application\DTO\ProjectListOutput;
use App\Monitoring\Catalog\Application\Query\ListProjectsQuery;
use App\Monitoring\Catalog\Application\QueryHandler\ListProjectsHandler;
use App\Monitoring\Catalog\Domain\Model\Framework;
use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Monitoring\VersionRegistry\Domain\Model\Product;
use App\Monitoring\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Monitoring\Catalog\ProjectFactory;

function stubListProjectsRepo(array $projects = [], int $count = 0): ProjectRepositoryInterface
{
    return new class ($projects, $count) implements ProjectRepositoryInterface {
        public function __construct(private readonly array $projects, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?Project
        {
            return null;
        }
        public function findBySlug(string $slug): ?Project
        {
            return null;
        }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
        {
            return null;
        }
        public function findExternalIdMapByProvider(Uuid $providerId): array
        {
            return [];
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->projects;
        }
        public function findByProviderId(Uuid $providerId): array
        {
            return [];
        }
        public function findAllWithProvider(): array
        {
            return [];
        }
        public function count(): int
        {
            return $this->count;
        }
        public function save(Project $project): void
        {
        }
        public function delete(Project $project): void
        {
        }
    };
}

function stubFrameworkRepoForList(): FrameworkRepositoryInterface
{
    return new class () implements FrameworkRepositoryInterface {
        public function findById(Uuid $id): ?Framework
        {
            return null;
        }
        /** @return list<Framework> */
        public function findAll(): array
        {
            return [];
        }
        /** @return list<Framework> */
        public function findByProjectId(Uuid $projectId): array
        {
            return [];
        }
        public function findByProjectIds(array $projectIds): array
        {
            return [];
        }
        public function findByNameAndProjectId(string $name, Uuid $projectId): ?Framework
        {
            return null;
        }
        /** @return list<Framework> */
        public function findByName(string $name): array
        {
            return [];
        }
        public function save(Framework $framework): void
        {
        }
        public function delete(Framework $framework): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

function stubDependencyRepoForList(): DependencyRepositoryInterface
{
    return new class () implements DependencyRepositoryInterface {
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
        }
        public function delete(Dependency $dependency): void
        {
        }
        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }
        public function countByProjects(array $projectIds): array
        {
            return [];
        }
        public function countVulnerabilitiesByProjects(array $projectIds): array
        {
            return [];
        }
        public function countVulnerabilitiesBySeverityForProjects(array $projectIds): array
        {
            return [];
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
        public function countOutdatedByProjects(array $projectIds): array
        {
            return [];
        }
    };
}

function stubProductRepoForList(): ProductRepositoryInterface
{
    return new class () implements ProductRepositoryInterface {
        public function findAll(): array
        {
            return [];
        }
        public function findByNameAndManager(string $name, mixed $pm): ?Product
        {
            return null;
        }
        public function findByNames(array $names): array
        {
            return [];
        }
        public function findByResolverSource(\App\Monitoring\VersionRegistry\Domain\Model\ResolverSource $source): array
        {
            return [];
        }
        public function findStale(\DateTimeImmutable $before): array
        {
            return [];
        }
        public function save(Product $product): void
        {
        }
    };
}

function stubProjectCoverage(): ProjectCoverageInterface
{
    return new class () implements ProjectCoverageInterface {
        public function findLatestForProjects(array $projectIds): array
        {
            return [];
        }
        public function findLatestJobsForProjects(array $projectIds): array
        {
            return [];
        }
    };
}

function stubProjectActivity(): ProjectActivityInterface
{
    return new class () implements ProjectActivityInterface {
        public function summarizeForProjects(array $projectIds, int $windowDays): array
        {
            return [];
        }
    };
}

function buildListProjectsHandler(array $projects = [], int $count = 0): ListProjectsHandler
{
    return new ListProjectsHandler(
        projectRepository: \stubListProjectsRepo($projects, $count),
        frameworkRepository: \stubFrameworkRepoForList(),
        dependencyRepository: \stubDependencyRepoForList(),
        productRepository: \stubProductRepoForList(),
        projectCoverage: \stubProjectCoverage(),
        projectActivity: \stubProjectActivity(),
        cache: \Tests\Helpers\CacheHelper::createTagAwareCache(),
    );
}

describe('ListProjectsHandler', function () {
    it('returns paginated projects', function () {
        $project1 = ProjectFactory::create(name: 'Project 1', slug: 'project-1');
        $project2 = ProjectFactory::create(name: 'Project 2', slug: 'project-2');

        $handler = \buildListProjectsHandler([$project1, $project2], 2);
        $result = $handler(new ListProjectsQuery(1, 20));

        expect($result)->toBeInstanceOf(ProjectListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no projects', function () {
        $handler = \buildListProjectsHandler([], 0);
        $result = $handler(new ListProjectsQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });

    it('enriches project output with defaults when no data', function () {
        $project = ProjectFactory::create(name: 'Empty', slug: 'empty');

        $handler = \buildListProjectsHandler([$project], 1);
        $result = $handler(new ListProjectsQuery(1, 20));

        $item = $result->pagination->items[0];
        expect($item->techStacks)->toBeEmpty();
        expect($item->techStacksCount)->toBe(0);
        expect($item->coveragePercent)->toBeNull();
        expect($item->dependenciesCount)->toBe(0);
        expect($item->vulnerabilitiesCount)->toBe(0);
        expect($item->lastActivityAt)->toBeNull();
        expect($item->commitsLast30d)->toBe(0);
        expect($item->commitsDailySeries)->toHaveCount(30);
    });

    it('enriches project with coverage and activity from batch resolvers', function () {
        $project = ProjectFactory::create(name: 'Rich', slug: 'rich');
        $projectId = $project->getId()->toRfc4122();

        $coverage = new class ($projectId) implements ProjectCoverageInterface {
            public function __construct(private readonly string $id)
            {
            }
            public function findLatestForProjects(array $projectIds): array
            {
                return [$this->id => 87.5];
            }
            public function findLatestJobsForProjects(array $projectIds): array
            {
                return [];
            }
        };

        $activity = new class ($projectId) implements ProjectActivityInterface {
            public function __construct(private readonly string $id)
            {
            }
            public function summarizeForProjects(array $projectIds, int $windowDays): array
            {
                return [
                    $this->id => new ProjectActivitySummary(
                        commitsCount: 12,
                        lastActivityAt: new DateTimeImmutable('2026-04-15T10:00:00+00:00'),
                        lastCommitSha: 'abc123',
                        dailyCommitCounts: \array_fill(0, $windowDays, 0),
                    ),
                ];
            }
        };

        $handler = new ListProjectsHandler(
            projectRepository: \stubListProjectsRepo([$project], 1),
            frameworkRepository: \stubFrameworkRepoForList(),
            dependencyRepository: \stubDependencyRepoForList(),
            productRepository: \stubProductRepoForList(),
            projectCoverage: $coverage,
            projectActivity: $activity,
            cache: \Tests\Helpers\CacheHelper::createTagAwareCache(),
        );

        $result = $handler(new ListProjectsQuery(1, 20));

        $item = $result->pagination->items[0];
        expect($item->coveragePercent)->toBe(87.5);
        expect($item->commitsLast30d)->toBe(12);
        expect($item->lastCommitSha)->toBe('abc123');
        expect($item->lastActivityAt)->toBe('2026-04-15T10:00:00+00:00');
    });
});

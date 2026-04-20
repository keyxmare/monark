<?php

declare(strict_types=1);

use App\Hub\Shared\Application\DTO\ProjectActivitySummary;
use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Hub\Shared\Domain\Port\ProjectActivityInterface;
use App\Hub\Shared\Domain\Port\ProjectCoverageInterface;
use App\Monitoring\Catalog\Application\DTO\ProjectOutput;
use App\Monitoring\Catalog\Application\Query\GetProjectQuery;
use App\Monitoring\Catalog\Application\QueryHandler\GetProjectHandler;
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

function stubGetProjectRepo(?Project $project = null): ProjectRepositoryInterface
{
    return new class ($project) implements ProjectRepositoryInterface {
        public function __construct(private readonly ?Project $project)
        {
        }
        public function findById(Uuid $id): ?Project
        {
            return $this->project;
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
            return [];
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
            return 0;
        }
        public function save(Project $project): void
        {
        }
        public function delete(Project $project): void
        {
        }
    };
}

function stubFrameworkRepoForGet(): FrameworkRepositoryInterface
{
    return new class () implements FrameworkRepositoryInterface {
        public function findById(Uuid $id): ?Framework
        {
            return null;
        }
        public function findAll(): array
        {
            return [];
        }
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

function stubDependencyRepoForGet(): DependencyRepositoryInterface
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

function stubProductRepoForGet(): ProductRepositoryInterface
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

function stubProjectCoverageForGet(): ProjectCoverageInterface
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

function stubProjectActivityForGet(): ProjectActivityInterface
{
    return new class () implements ProjectActivityInterface {
        public function summarizeForProjects(array $projectIds, int $windowDays): array
        {
            return [];
        }
    };
}

function buildGetProjectHandler(?Project $project): GetProjectHandler
{
    return new GetProjectHandler(
        projectRepository: \stubGetProjectRepo($project),
        frameworkRepository: \stubFrameworkRepoForGet(),
        dependencyRepository: \stubDependencyRepoForGet(),
        productRepository: \stubProductRepoForGet(),
        projectCoverage: \stubProjectCoverageForGet(),
        projectActivity: \stubProjectActivityForGet(),
        cache: \Tests\Helpers\CacheHelper::createTagAwareCache(),
    );
}

describe('GetProjectHandler', function () {
    it('returns a project by id', function () {
        $project = ProjectFactory::create(name: 'My Project', slug: 'my-project');
        $handler = \buildGetProjectHandler($project);
        $result = $handler(new GetProjectQuery($project->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(ProjectOutput::class);
        expect($result->name)->toBe('My Project');
        expect($result->slug)->toBe('my-project');
        expect($result->commitsDailySeries)->toHaveCount(30);
        expect($result->techStacks)->toBeEmpty();
    });

    it('throws not found when project does not exist', function () {
        $handler = \buildGetProjectHandler(null);
        $handler(new GetProjectQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);

    it('surfaces coverage and activity in output', function () {
        $project = ProjectFactory::create(name: 'Rich', slug: 'rich');
        $projectId = $project->getId()->toRfc4122();

        $coverage = new class ($projectId) implements ProjectCoverageInterface {
            public function __construct(private readonly string $id)
            {
            }
            public function findLatestForProjects(array $projectIds): array
            {
                return [$this->id => 62.0];
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
                        commitsCount: 5,
                        lastActivityAt: new DateTimeImmutable('2026-04-18T12:00:00+00:00'),
                        lastCommitSha: 'sha-abc',
                        dailyCommitCounts: \array_fill(0, $windowDays, 0),
                    ),
                ];
            }
        };

        $handler = new GetProjectHandler(
            projectRepository: \stubGetProjectRepo($project),
            frameworkRepository: \stubFrameworkRepoForGet(),
            dependencyRepository: \stubDependencyRepoForGet(),
            productRepository: \stubProductRepoForGet(),
            projectCoverage: $coverage,
            projectActivity: $activity,
            cache: \Tests\Helpers\CacheHelper::createTagAwareCache(),
        );

        $result = $handler(new GetProjectQuery($projectId));

        expect($result->coveragePercent)->toBe(62.0);
        expect($result->commitsLast30d)->toBe(5);
        expect($result->lastCommitSha)->toBe('sha-abc');
    });
});

<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\QueryHandler;

use App\Hub\Shared\Application\DTO\PaginatedOutput;
use App\Hub\Shared\Domain\Port\ProjectActivityInterface;
use App\Hub\Shared\Domain\Port\ProjectCoverageInterface;
use App\Monitoring\Catalog\Application\DTO\ProjectListOutput;
use App\Monitoring\Catalog\Application\Mapper\ProjectMapper;
use App\Monitoring\Catalog\Application\Query\ListProjectsQuery;
use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Monitoring\VersionRegistry\Domain\Model\ProductType;
use App\Monitoring\VersionRegistry\Domain\Model\ResolverSource;
use App\Monitoring\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListProjectsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private FrameworkRepositoryInterface $frameworkRepository,
        private DependencyRepositoryInterface $dependencyRepository,
        private ProductRepositoryInterface $productRepository,
        private ProjectCoverageInterface $projectCoverage,
        private ProjectActivityInterface $projectActivity,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(ListProjectsQuery $query): ProjectListOutput
    {
        $cacheKey = \sprintf('projects_list_p%d_pp%d', $query->page, $query->perPage);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query): ProjectListOutput {
            $item->expiresAfter(60);
            $item->tag(['projects', 'projects_summary']);

            $projects = $this->projectRepository->findAll($query->page, $query->perPage);
            $total = $this->projectRepository->count();

            $projectUuids = \array_map(static fn (Project $p) => $p->getId(), $projects);

            $frameworks = $this->frameworkRepository->findByProjectIds($projectUuids);
            $dependenciesCount = $this->dependencyRepository->countByProjects($projectUuids);
            $outdatedDependenciesCount = $this->dependencyRepository->countOutdatedByProjects($projectUuids);
            $vulnerabilitiesCount = $this->dependencyRepository->countVulnerabilitiesByProjects($projectUuids);
            $vulnerabilitiesBySeverity = $this->dependencyRepository->countVulnerabilitiesBySeverityForProjects($projectUuids);
            $coverage = $this->projectCoverage->findLatestForProjects($projectUuids);
            $coverageJobs = $this->projectCoverage->findLatestJobsForProjects($projectUuids);
            $activity = $this->projectActivity->summarizeForProjects($projectUuids, ProjectMapper::ACTIVITY_WINDOW_DAYS);

            $latestVersionByProduct = [];
            $languageProducts = [];
            foreach ($this->productRepository->findByResolverSource(ResolverSource::EndOfLife) as $product) {
                $key = \strtolower($product->getName());
                $latestVersionByProduct[$key] = $product->getLatestVersion();
                if ($product->getType() === ProductType::Language) {
                    $languageProducts[$key] = $product;
                }
            }

            $items = \array_map(
                static function (Project $project) use (
                    $frameworks,
                    $dependenciesCount,
                    $outdatedDependenciesCount,
                    $vulnerabilitiesCount,
                    $vulnerabilitiesBySeverity,
                    $coverage,
                    $coverageJobs,
                    $activity,
                    $latestVersionByProduct,
                    $languageProducts,
                ) {
                    $id = $project->getId()->toRfc4122();

                    return ProjectMapper::toOutput(
                        project: $project,
                        frameworks: $frameworks[$id] ?? [],
                        coveragePercent: $coverage[$id] ?? null,
                        dependenciesCount: $dependenciesCount[$id] ?? 0,
                        outdatedDependenciesCount: $outdatedDependenciesCount[$id] ?? 0,
                        vulnerabilitiesCount: $vulnerabilitiesCount[$id] ?? 0,
                        activity: $activity[$id] ?? null,
                        latestVersionByProduct: $latestVersionByProduct,
                        languageProducts: $languageProducts,
                        vulnerabilitiesBySeverity: $vulnerabilitiesBySeverity[$id] ?? null,
                        coverageJobs: $coverageJobs[$id] ?? [],
                    );
                },
                $projects,
            );

            return new ProjectListOutput(
                pagination: new PaginatedOutput(
                    items: $items,
                    total: $total,
                    page: $query->page,
                    perPage: $query->perPage,
                ),
            );
        });
    }
}

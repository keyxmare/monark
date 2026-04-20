<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\QueryHandler;

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Hub\Shared\Domain\Port\ProjectActivityInterface;
use App\Hub\Shared\Domain\Port\ProjectCoverageInterface;
use App\Monitoring\Catalog\Application\DTO\ProjectOutput;
use App\Monitoring\Catalog\Application\Mapper\ProjectMapper;
use App\Monitoring\Catalog\Application\Query\GetProjectQuery;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Monitoring\VersionRegistry\Domain\Model\ProductType;
use App\Monitoring\VersionRegistry\Domain\Model\ResolverSource;
use App\Monitoring\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProjectHandler
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

    public function __invoke(GetProjectQuery $query): ProjectOutput
    {
        $cacheKey = \sprintf('project_%s', $query->projectId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query): ProjectOutput {
            $item->expiresAfter(60);
            $item->tag(['projects', 'projects_summary', \sprintf('project_%s', $query->projectId)]);

            $projectId = Uuid::fromString($query->projectId);
            $project = $this->projectRepository->findById($projectId);
            if ($project === null) {
                throw NotFoundException::forEntity('Project', $query->projectId);
            }

            $idString = $projectId->toRfc4122();
            $frameworks = $this->frameworkRepository->findByProjectIds([$projectId])[$idString] ?? [];
            $dependenciesCount = $this->dependencyRepository->countByProjects([$projectId])[$idString] ?? 0;
            $outdatedDependenciesCount = $this->dependencyRepository->countOutdatedByProjects([$projectId])[$idString] ?? 0;
            $vulnerabilitiesCount = $this->dependencyRepository->countVulnerabilitiesByProjects([$projectId])[$idString] ?? 0;
            $vulnerabilitiesBySeverity = $this->dependencyRepository->countVulnerabilitiesBySeverityForProjects([$projectId])[$idString] ?? null;
            $coveragePercent = $this->projectCoverage->findLatestForProjects([$projectId])[$idString] ?? null;
            $coverageJobs = $this->projectCoverage->findLatestJobsForProjects([$projectId])[$idString] ?? [];
            $activity = $this->projectActivity->summarizeForProjects([$projectId], ProjectMapper::ACTIVITY_WINDOW_DAYS)[$idString] ?? null;

            $latestVersionByProduct = [];
            $languageProducts = [];
            foreach ($this->productRepository->findByResolverSource(ResolverSource::EndOfLife) as $product) {
                $key = \strtolower($product->getName());
                $latestVersionByProduct[$key] = $product->getLatestVersion();
                if ($product->getType() === ProductType::Language) {
                    $languageProducts[$key] = $product;
                }
            }

            return ProjectMapper::toOutput(
                project: $project,
                frameworks: $frameworks,
                coveragePercent: $coveragePercent,
                dependenciesCount: $dependenciesCount,
                outdatedDependenciesCount: $outdatedDependenciesCount,
                vulnerabilitiesCount: $vulnerabilitiesCount,
                activity: $activity,
                latestVersionByProduct: $latestVersionByProduct,
                languageProducts: $languageProducts,
                vulnerabilitiesBySeverity: $vulnerabilitiesBySeverity,
                coverageJobs: $coverageJobs,
            );
        });
    }
}

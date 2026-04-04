<?php

declare(strict_types=1);

namespace App\Coverage\Application\QueryHandler;

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Application\DTO\CoverageDashboardOutput;
use App\Coverage\Application\DTO\CoverageProjectOutput;
use App\Coverage\Application\DTO\CoverageSummaryOutput;
use App\Coverage\Application\Query\GetCoverageDashboardQuery;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use DateTimeInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetCoverageDashboardQueryHandler
{
    private const float THRESHOLD = 80.0;

    public function __construct(
        private CoverageSnapshotRepositoryInterface $snapshotRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(GetCoverageDashboardQuery $query): CoverageDashboardOutput
    {
        $latestSnapshots = $this->snapshotRepository->findLatestPerProject();
        $previousSnapshots = $this->snapshotRepository->findPreviousPerProject();

        $previousByProjectId = [];
        foreach ($previousSnapshots as $snapshot) {
            $previousByProjectId[$snapshot->getProjectId()->toRfc4122()] = $snapshot;
        }

        $allProjects = $this->projectRepository->findAllWithProvider();
        $projectsById = [];
        foreach ($allProjects as $project) {
            $projectsById[$project->getId()->toRfc4122()] = $project;
        }

        $projects = [];
        $totalCoverage = 0.0;
        $coveredProjects = 0;
        $aboveThreshold = 0;
        $belowThreshold = 0;

        foreach ($latestSnapshots as $snapshot) {
            $projectIdStr = $snapshot->getProjectId()->toRfc4122();
            $project = $projectsById[$projectIdStr] ?? null;

            if ($project === null) {
                continue;
            }

            $previous = $previousByProjectId[$projectIdStr] ?? null;
            $trend = $previous !== null
                ? \round($snapshot->getCoveragePercent() - $previous->getCoveragePercent(), 2)
                : null;

            $coverage = $snapshot->getCoveragePercent();
            $totalCoverage += $coverage;
            ++$coveredProjects;

            if ($coverage >= self::THRESHOLD) {
                ++$aboveThreshold;
            } else {
                ++$belowThreshold;
            }

            $projects[] = new CoverageProjectOutput(
                projectId: $projectIdStr,
                projectName: $project->getName(),
                projectSlug: $project->getSlug(),
                coveragePercent: $coverage,
                trend: $trend,
                source: $snapshot->getSource()->value,
                commitHash: $snapshot->getCommitHash(),
                ref: $snapshot->getRef(),
                syncedAt: $snapshot->getCreatedAt()->format(DateTimeInterface::ATOM),
            );
        }

        $totalProjects = \count($allProjects);
        $averageCoverage = $coveredProjects > 0
            ? \round($totalCoverage / $coveredProjects, 2)
            : null;

        $overallTrend = null;
        if (\count($previousByProjectId) > 0 && $coveredProjects > 0) {
            $previousTotal = 0.0;
            $previousCount = 0;
            foreach ($latestSnapshots as $snapshot) {
                $projectIdStr = $snapshot->getProjectId()->toRfc4122();
                if (isset($previousByProjectId[$projectIdStr])) {
                    $previousTotal += $previousByProjectId[$projectIdStr]->getCoveragePercent();
                    ++$previousCount;
                }
            }
            if ($previousCount > 0 && $averageCoverage !== null) {
                $overallTrend = \round($averageCoverage - ($previousTotal / $previousCount), 2);
            }
        }

        $summary = new CoverageSummaryOutput(
            averageCoverage: $averageCoverage,
            totalProjects: $totalProjects,
            coveredProjects: $coveredProjects,
            aboveThreshold: $aboveThreshold,
            belowThreshold: $belowThreshold,
            trend: $overallTrend,
        );

        return new CoverageDashboardOutput(summary: $summary, projects: $projects);
    }
}

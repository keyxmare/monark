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

            $jobs = $snapshot->getJobs();
            $jobOutputs = [];

            if ($jobs !== null && $jobs !== []) {
                $previousJobs = $previous?->getJobs();
                $previousJobsByName = [];
                if ($previousJobs !== null) {
                    foreach ($previousJobs as $pj) {
                        $previousJobsByName[$pj['name']] = $pj['percent'];
                    }
                }

                foreach ($jobs as $job) {
                    $jobTrend = isset($previousJobsByName[$job['name']])
                        ? \round($job['percent'] - $previousJobsByName[$job['name']], 2)
                        : null;

                    $jobOutputs[] = [
                        'name' => $job['name'],
                        'percent' => $job['percent'],
                        'trend' => $jobTrend,
                    ];
                }

                $coverage = \round(\array_sum(\array_column($jobs, 'percent')) / \count($jobs), 2);
            } else {
                $coverage = $snapshot->getCoveragePercent();
            }

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
                jobs: $jobOutputs,
            );
        }

        $coveredProjectIds = \array_map(
            static fn (CoverageProjectOutput $p): string => $p->projectId,
            $projects,
        );

        foreach ($allProjects as $project) {
            $projectIdStr = $project->getId()->toRfc4122();
            if (!\in_array($projectIdStr, $coveredProjectIds, true)) {
                ++$belowThreshold;
                $projects[] = new CoverageProjectOutput(
                    projectId: $projectIdStr,
                    projectName: $project->getName(),
                    projectSlug: $project->getSlug(),
                    coveragePercent: 0.0,
                    trend: null,
                    source: null,
                    commitHash: null,
                    ref: null,
                    syncedAt: null,
                );
            }
        }

        $totalProjects = \count($allProjects);
        $averageCoverage = $totalProjects > 0
            ? \round($totalCoverage / $totalProjects, 2)
            : 0.0;

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
            if ($previousCount > 0) {
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

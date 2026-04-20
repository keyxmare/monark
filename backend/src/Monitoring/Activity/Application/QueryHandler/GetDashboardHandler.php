<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Application\QueryHandler;

use App\Hub\Shared\Domain\Port\BranchCounterInterface;
use App\Hub\Shared\Domain\Port\CommitCounterInterface;
use App\Hub\Shared\Domain\Port\CoverageSummaryInterface;
use App\Hub\Shared\Domain\Port\DependencyCounterInterface;
use App\Hub\Shared\Domain\Port\LanguagesSummaryInterface;
use App\Hub\Shared\Domain\Port\MostActiveProjectsInterface;
use App\Hub\Shared\Domain\Port\ProjectCounterInterface;
use App\Hub\Shared\Domain\Port\ProjectDriftSummaryInterface;
use App\Hub\Shared\Domain\Port\ProvidersBreakdownInterface;
use App\Hub\Shared\Domain\Port\TopOutdatedDependenciesInterface;
use App\Hub\Shared\Domain\Port\VulnerabilityCounterInterface;
use App\Hub\Shared\Domain\Port\WeakestCoverageProjectsInterface;
use App\Monitoring\Activity\Application\DTO\DashboardOutput;
use App\Monitoring\Activity\Application\Query\GetDashboardQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDashboardHandler
{
    private const int WINDOW_DAYS = 30;
    private const int LANGUAGES_LIMIT = 7;
    private const int HIGHLIGHT_LIMIT = 5;
    private const int OUTDATED_OCCURRENCES_LIMIT = 5;

    public function __construct(
        private ProjectCounterInterface $projectCounter,
        private CommitCounterInterface $commitCounter,
        private BranchCounterInterface $branchCounter,
        private DependencyCounterInterface $dependencyCounter,
        private VulnerabilityCounterInterface $vulnerabilityCounter,
        private CoverageSummaryInterface $coverageSummary,
        private LanguagesSummaryInterface $languagesSummary,
        private ProvidersBreakdownInterface $providersBreakdown,
        private MostActiveProjectsInterface $mostActiveProjects,
        private WeakestCoverageProjectsInterface $weakestCoverageProjects,
        private TopOutdatedDependenciesInterface $topOutdatedDependencies,
        private ProjectDriftSummaryInterface $projectDriftSummary,
    ) {
    }

    public function __invoke(GetDashboardQuery $query): DashboardOutput
    {
        $totalProjects = $this->projectCounter->countTracked();

        return new DashboardOutput(
            projects: $totalProjects,
            commits30d: $this->commitCounter->countDistinctSince(self::WINDOW_DAYS),
            activeBranches30d: $this->branchCounter->countActiveSince(self::WINDOW_DAYS),
            dependenciesTracked: $this->dependencyCounter->countTracked(),
            vulnerabilities: $this->vulnerabilityCounter->countAll(),
            coveragePercent: $this->coverageSummary->averagePercent(),
            coverageHealthPercent: $this->coverageSummary->averagePercentOverAllProjects($totalProjects),
            languages: $this->languagesSummary->topLanguages(self::LANGUAGES_LIMIT),
            hosts: $this->providersBreakdown->byType(),
            mostActive: $this->mostActiveProjects->top(self::HIGHLIGHT_LIMIT, self::WINDOW_DAYS),
            weakestCoverage: $this->weakestCoverageProjects->lowest(self::HIGHLIGHT_LIMIT),
            topOutdatedDependencies: $this->topOutdatedDependencies->top(
                self::HIGHLIGHT_LIMIT,
                self::OUTDATED_OCCURRENCES_LIMIT,
            ),
            projectDrift: $this->projectDriftSummary->byProject(self::HIGHLIGHT_LIMIT),
        );
    }
}

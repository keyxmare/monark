<?php

declare(strict_types=1);

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
use App\Monitoring\Activity\Application\QueryHandler\GetDashboardHandler;

function dashProjectCounter(int $count): ProjectCounterInterface
{
    return new class ($count) implements ProjectCounterInterface {
        public function __construct(private readonly int $count)
        {
        }

        public function countTracked(): int
        {
            return $this->count;
        }
    };
}

function dashCommitCounter(int $count): CommitCounterInterface
{
    return new class ($count) implements CommitCounterInterface {
        public function __construct(private readonly int $count)
        {
        }

        public function countDistinctSince(int $days): int
        {
            return $this->count;
        }
    };
}

function dashBranchCounter(int $count): BranchCounterInterface
{
    return new class ($count) implements BranchCounterInterface {
        public function __construct(private readonly int $count)
        {
        }

        public function countActiveSince(int $days): int
        {
            return $this->count;
        }
    };
}

function dashDependencyCounter(int $count): DependencyCounterInterface
{
    return new class ($count) implements DependencyCounterInterface {
        public function __construct(private readonly int $count)
        {
        }

        public function countTracked(): int
        {
            return $this->count;
        }
    };
}

function dashVulnCounter(int $count): VulnerabilityCounterInterface
{
    return new class ($count) implements VulnerabilityCounterInterface {
        public function __construct(private readonly int $count)
        {
        }

        public function countAll(): int
        {
            return $this->count;
        }
    };
}

function dashCoverageSummary(?float $avg): CoverageSummaryInterface
{
    return new class ($avg) implements CoverageSummaryInterface {
        public function __construct(private readonly ?float $avg)
        {
        }

        public function averagePercent(): ?float
        {
            return $this->avg;
        }

        public function averagePercentOverAllProjects(int $totalProjects): ?float
        {
            return $this->avg;
        }
    };
}

/** @param list<array{name: string, projects_count: int}> $languages */
function dashLanguagesSummary(array $languages = []): LanguagesSummaryInterface
{
    return new class ($languages) implements LanguagesSummaryInterface {
        /** @param list<array{name: string, projects_count: int}> $languages */
        public function __construct(private readonly array $languages)
        {
        }

        public function topLanguages(int $limit): array
        {
            return \array_slice($this->languages, 0, $limit);
        }
    };
}

/** @param list<array{type: string, label: string, connected: bool, projects_count: int}> $hosts */
function dashProvidersBreakdown(array $hosts = []): ProvidersBreakdownInterface
{
    return new class ($hosts) implements ProvidersBreakdownInterface {
        /** @param list<array{type: string, label: string, connected: bool, projects_count: int}> $hosts */
        public function __construct(private readonly array $hosts)
        {
        }

        public function byType(): array
        {
            return $this->hosts;
        }
    };
}

function dashMostActive(): MostActiveProjectsInterface
{
    return new class () implements MostActiveProjectsInterface {
        public function top(int $limit, int $windowDays): array
        {
            return [];
        }
    };
}

function dashWeakestCoverage(): WeakestCoverageProjectsInterface
{
    return new class () implements WeakestCoverageProjectsInterface {
        public function lowest(int $limit): array
        {
            return [];
        }
    };
}

function dashTopOutdated(): TopOutdatedDependenciesInterface
{
    return new class () implements TopOutdatedDependenciesInterface {
        public function top(int $limit, int $occurrencesLimit): array
        {
            return [];
        }
    };
}

function dashProjectDrift(): ProjectDriftSummaryInterface
{
    return new class () implements ProjectDriftSummaryInterface {
        public function byProject(int $limit): array
        {
            return [];
        }
    };
}

/**
 * @param list<array{name: string, projects_count: int}>                                 $languages
 * @param list<array{type: string, label: string, connected: bool, projects_count: int}> $hosts
 */
function makeDashboardHandler(
    int $projects = 0,
    int $commits = 0,
    int $branches = 0,
    int $deps = 0,
    int $vulns = 0,
    ?float $coverage = null,
    array $languages = [],
    array $hosts = [],
): GetDashboardHandler {
    return new GetDashboardHandler(
        \dashProjectCounter($projects),
        \dashCommitCounter($commits),
        \dashBranchCounter($branches),
        \dashDependencyCounter($deps),
        \dashVulnCounter($vulns),
        \dashCoverageSummary($coverage),
        \dashLanguagesSummary($languages),
        \dashProvidersBreakdown($hosts),
        \dashMostActive(),
        \dashWeakestCoverage(),
        \dashTopOutdated(),
        \dashProjectDrift(),
    );
}

describe('GetDashboardHandler', function () {
    it('aggregates all eight metrics from their ports', function () {
        $langs = [['name' => 'PHP', 'projects_count' => 10]];
        $hosts = [['type' => 'gitlab', 'label' => 'GitLab', 'connected' => true, 'projects_count' => 19]];
        $handler = \makeDashboardHandler(19, 120, 7, 1088, 132, 74.5, $langs, $hosts);

        $result = $handler(new GetDashboardQuery('00000000-0000-0000-0000-000000000001'));

        expect($result)->toBeInstanceOf(DashboardOutput::class);
        expect($result->projects)->toBe(19);
        expect($result->commits30d)->toBe(120);
        expect($result->activeBranches30d)->toBe(7);
        expect($result->dependenciesTracked)->toBe(1088);
        expect($result->vulnerabilities)->toBe(132);
        expect($result->coveragePercent)->toBe(74.5);
        expect($result->languages)->toBe($langs);
        expect($result->hosts)->toBe($hosts);
    });

    it('passes null coverage through when no snapshot exists', function () {
        $handler = \makeDashboardHandler(coverage: null);

        $result = $handler(new GetDashboardQuery('u1'));

        expect($result->coveragePercent)->toBeNull();
    });

    it('returns empty languages and hosts lists when none tracked', function () {
        $handler = \makeDashboardHandler();

        $result = $handler(new GetDashboardQuery('u1'));

        expect($result->languages)->toBe([]);
        expect($result->hosts)->toBe([]);
    });

    it('serialises the DTO with snake_case keys including languages and hosts', function () {
        $dto = new DashboardOutput(
            projects: 19,
            commits30d: 120,
            activeBranches30d: 7,
            dependenciesTracked: 1088,
            vulnerabilities: 132,
            coveragePercent: 74.5,
            coverageHealthPercent: 70.5,
            languages: [['name' => 'PHP', 'projects_count' => 10]],
            hosts: [['type' => 'gitlab', 'label' => 'GitLab', 'connected' => true, 'projects_count' => 19]],
            mostActive: [],
            weakestCoverage: [],
            topOutdatedDependencies: [],
            projectDrift: [],
        );

        expect($dto->toArray())->toBe([
            'projects' => 19,
            'commits_30d' => 120,
            'active_branches_30d' => 7,
            'dependencies_tracked' => 1088,
            'vulnerabilities' => 132,
            'coverage_percent' => 74.5,
            'coverage_health_percent' => 70.5,
            'languages' => [['name' => 'PHP', 'projects_count' => 10]],
            'hosts' => [['type' => 'gitlab', 'label' => 'GitLab', 'connected' => true, 'projects_count' => 19]],
            'most_active' => [],
            'weakest_coverage' => [],
            'top_outdated_dependencies' => [],
            'project_drift' => [],
        ]);
    });
});

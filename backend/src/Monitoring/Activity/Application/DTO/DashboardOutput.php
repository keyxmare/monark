<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Application\DTO;

final readonly class DashboardOutput
{
    /**
     * @param list<array{name: string, projects_count: int}>                                         $languages
     * @param list<array{type: string, label: string, connected: bool, projects_count: int}>        $hosts
     * @param list<array{id: string, name: string, slug: string, commits: int}>                     $mostActive
     * @param list<array{id: string, name: string, slug: string, coveragePercent: float}>           $weakestCoverage
     * @param list<array{
     *     name: string,
     *     projectsCount: int,
     *     occurrences: list<array{projectName: string, projectSlug: string, currentVersion: string, latestVersion: string}>
     * }>                                                                                           $topOutdatedDependencies
     * @param list<array{name: string, slug: string, total: int, upToDate: int, drift: int}>       $projectDrift
     */
    public function __construct(
        public int $projects,
        public int $commits30d,
        public int $activeBranches30d,
        public int $dependenciesTracked,
        public int $vulnerabilities,
        public ?float $coveragePercent,
        public ?float $coverageHealthPercent,
        public array $languages,
        public array $hosts,
        public array $mostActive,
        public array $weakestCoverage,
        public array $topOutdatedDependencies,
        public array $projectDrift,
    ) {
    }

    /**
     * @return array{
     *     projects: int,
     *     commits_30d: int,
     *     active_branches_30d: int,
     *     dependencies_tracked: int,
     *     vulnerabilities: int,
     *     coverage_percent: ?float,
     *     coverage_health_percent: ?float,
     *     languages: list<array{name: string, projects_count: int}>,
     *     hosts: list<array{type: string, label: string, connected: bool, projects_count: int}>,
     *     most_active: list<array{id: string, name: string, slug: string, commits: int}>,
     *     weakest_coverage: list<array{id: string, name: string, slug: string, coveragePercent: float}>,
     *     top_outdated_dependencies: list<array{
     *         name: string,
     *         projectsCount: int,
     *         occurrences: list<array{projectName: string, projectSlug: string, currentVersion: string, latestVersion: string}>
     *     }>,
     *     project_drift: list<array{name: string, slug: string, total: int, upToDate: int, drift: int}>
     * }
     */
    public function toArray(): array
    {
        return [
            'projects' => $this->projects,
            'commits_30d' => $this->commits30d,
            'active_branches_30d' => $this->activeBranches30d,
            'dependencies_tracked' => $this->dependenciesTracked,
            'vulnerabilities' => $this->vulnerabilities,
            'coverage_percent' => $this->coveragePercent,
            'coverage_health_percent' => $this->coverageHealthPercent,
            'languages' => $this->languages,
            'hosts' => $this->hosts,
            'most_active' => $this->mostActive,
            'weakest_coverage' => $this->weakestCoverage,
            'top_outdated_dependencies' => $this->topOutdatedDependencies,
            'project_drift' => $this->projectDrift,
        ];
    }
}

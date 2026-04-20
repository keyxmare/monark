<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\DTO;

final readonly class ProjectOutput
{
    /**
     * @param list<TechStackSummary>                                                $techStacks
     * @param list<RuntimeSummary>                                                  $runtimes
     * @param array{critical: int, high: int, medium: int, low: int}                $vulnerabilitiesBySeverity
     * @param list<array{name: string, percent: float}>                             $coverageJobs
     * @param list<int>                                                             $commitsDailySeries Oldest → newest, 30 points
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $repositoryUrl,
        public string $defaultBranch,
        public string $visibility,
        public string $ownerId,
        public ?string $providerId,
        public ?string $externalId,
        public string $createdAt,
        public string $updatedAt,
        public array $techStacks,
        public int $techStacksCount,
        public array $runtimes,
        public FrameworkLagSummary $frameworkLag,
        public ?float $coveragePercent,
        public array $coverageJobs,
        public int $dependenciesCount,
        public int $outdatedDependenciesCount,
        public int $vulnerabilitiesCount,
        public array $vulnerabilitiesBySeverity,
        public ?string $lastActivityAt,
        public ?string $lastCommitSha,
        public int $commitsLast30d,
        public array $commitsDailySeries,
    ) {
    }
}

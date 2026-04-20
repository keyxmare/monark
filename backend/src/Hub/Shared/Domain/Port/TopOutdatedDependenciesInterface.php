<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface TopOutdatedDependenciesInterface
{
    /**
     * Top-N outdated dependencies ranked by project occurrence count, descending.
     *
     * @return list<array{
     *     name: string,
     *     projectsCount: int,
     *     occurrences: list<array{projectName: string, projectSlug: string, currentVersion: string, latestVersion: string}>
     * }>
     */
    public function top(int $limit, int $occurrencesLimit): array;
}

<?php

declare(strict_types=1);

namespace App\Hub\Shared\Application\DTO;

final readonly class HubSummaryOutput
{
    public function __construct(
        public int $reposTracked,
        public int $commits30d,
        public int $focusSecondsToday,
        public int $cveCount,
        public ?float $coveragePercent,
    ) {
    }

    /**
     * @return array{repos_tracked: int, commits_30d: int, focus_seconds_today: int, cve_count: int, coverage_percent: ?float}
     */
    public function toArray(): array
    {
        return [
            'repos_tracked' => $this->reposTracked,
            'commits_30d' => $this->commits30d,
            'focus_seconds_today' => $this->focusSecondsToday,
            'cve_count' => $this->cveCount,
            'coverage_percent' => $this->coveragePercent,
        ];
    }
}

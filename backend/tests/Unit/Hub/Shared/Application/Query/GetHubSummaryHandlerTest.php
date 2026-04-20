<?php

declare(strict_types=1);

use App\Hub\Shared\Application\DTO\HubSummaryOutput;
use App\Hub\Shared\Application\Query\GetHubSummaryHandler;
use App\Hub\Shared\Application\Query\GetHubSummaryQuery;
use App\Hub\Shared\Domain\Port\CommitCounterInterface;
use App\Hub\Shared\Domain\Port\CoverageSummaryInterface;
use App\Hub\Shared\Domain\Port\ProjectCounterInterface;
use App\Hub\Shared\Domain\Port\VulnerabilityCounterInterface;
use Symfony\Component\Clock\MockClock;

function stubProjectCounter(int $count): ProjectCounterInterface
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

function stubCommitCounter(int $count): CommitCounterInterface
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

function stubVulnCounter(int $count): VulnerabilityCounterInterface
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

function stubCoverageSummary(?float $avg): CoverageSummaryInterface
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

function makeHandler(
    int $projects,
    int $commits,
    int $vulns,
    ?float $cov,
    string $clockIso,
    string $appTimezone = 'UTC',
): GetHubSummaryHandler {
    return new GetHubSummaryHandler(
        \stubProjectCounter($projects),
        \stubCommitCounter($commits),
        \stubVulnCounter($vulns),
        \stubCoverageSummary($cov),
        new MockClock($clockIso),
        $appTimezone,
    );
}

describe('GetHubSummaryHandler', function () {
    it('returns project, commit, vuln and coverage data from their ports', function () {
        $handler = \makeHandler(14, 42, 9, 78.5, '2026-04-20T10:15:00+00:00');

        $result = $handler(new GetHubSummaryQuery());

        expect($result)->toBeInstanceOf(HubSummaryOutput::class);
        expect($result->reposTracked)->toBe(14);
        expect($result->commits30d)->toBe(42);
        expect($result->cveCount)->toBe(9);
        expect($result->coveragePercent)->toBe(78.5);
    });

    it('passes null coverage through when no snapshot exists', function () {
        $handler = \makeHandler(0, 0, 0, null, '2026-04-20T10:15:00+00:00');

        $result = $handler(new GetHubSummaryQuery());

        expect($result->coveragePercent)->toBeNull();
    });

    it('computes focus seconds as elapsed time since midnight in the app timezone', function () {
        $handler = \makeHandler(0, 0, 0, null, '2026-04-20T10:15:00+00:00', 'Europe/Paris');

        $result = $handler(new GetHubSummaryQuery());

        // 10:15 UTC projected to Europe/Paris (CEST, +02:00) = 12:15 local → 12*3600 + 15*60 = 44100
        expect($result->focusSecondsToday)->toBe(44_100);
    });

    it('computes focus seconds in UTC when configured to UTC', function () {
        $handler = \makeHandler(0, 0, 0, null, '2026-04-20T10:15:00+00:00', 'UTC');

        $result = $handler(new GetHubSummaryQuery());

        expect($result->focusSecondsToday)->toBe(36_900);
    });

    it('returns zero focus seconds right at midnight', function () {
        $handler = \makeHandler(0, 0, 0, null, '2026-04-20T00:00:00+00:00', 'UTC');

        $result = $handler(new GetHubSummaryQuery());

        expect($result->focusSecondsToday)->toBe(0);
    });

    it('serialises the DTO with snake_case keys', function () {
        $dto = new HubSummaryOutput(
            reposTracked: 14,
            commits30d: 42,
            focusSecondsToday: 36_900,
            cveCount: 9,
            coveragePercent: 78.5,
        );

        expect($dto->toArray())->toBe([
            'repos_tracked' => 14,
            'commits_30d' => 42,
            'focus_seconds_today' => 36_900,
            'cve_count' => 9,
            'coverage_percent' => 78.5,
        ]);
    });
});

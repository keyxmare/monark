<?php

declare(strict_types=1);

namespace App\Hub\Shared\Application\Query;

use App\Hub\Shared\Application\DTO\HubSummaryOutput;
use App\Hub\Shared\Domain\Port\CommitCounterInterface;
use App\Hub\Shared\Domain\Port\CoverageSummaryInterface;
use App\Hub\Shared\Domain\Port\ProjectCounterInterface;
use App\Hub\Shared\Domain\Port\VulnerabilityCounterInterface;
use DateTimeZone;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetHubSummaryHandler
{
    private const int COMMITS_WINDOW_DAYS = 30;

    public function __construct(
        private ProjectCounterInterface $projectCounter,
        private CommitCounterInterface $commitCounter,
        private VulnerabilityCounterInterface $vulnerabilityCounter,
        private CoverageSummaryInterface $coverageSummary,
        private ClockInterface $clock,
        private string $appTimezone,
    ) {
    }

    public function __invoke(GetHubSummaryQuery $query): HubSummaryOutput
    {
        $now = $this->clock->now()->setTimezone(new DateTimeZone($this->appTimezone));
        $midnight = $now->setTime(0, 0, 0);
        $focusSeconds = $now->getTimestamp() - $midnight->getTimestamp();

        return new HubSummaryOutput(
            reposTracked: $this->projectCounter->countTracked(),
            commits30d: $this->commitCounter->countDistinctSince(self::COMMITS_WINDOW_DAYS),
            focusSecondsToday: $focusSeconds,
            cveCount: $this->vulnerabilityCounter->countAll(),
            coveragePercent: $this->coverageSummary->averagePercent(),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Hub\Shared\Application\DTO;

use DateTimeImmutable;

final readonly class ProjectActivitySummary
{
    /**
     * @param list<int> $dailyCommitCounts Oldest → newest, length = window days
     */
    public function __construct(
        public int $commitsCount,
        public ?DateTimeImmutable $lastActivityAt,
        public ?string $lastCommitSha,
        public array $dailyCommitCounts,
    ) {
    }

    /**
     */
    public static function empty(int $windowDays): self
    {
        return new self(
            commitsCount: 0,
            lastActivityAt: null,
            lastCommitSha: null,
            dailyCommitCounts: \array_fill(0, $windowDays, 0),
        );
    }
}

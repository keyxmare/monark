<?php

declare(strict_types=1);

namespace App\Activity\Domain\Event;

use DateTimeImmutable;

final readonly class BuildMetricRecorded
{
    public function __construct(
        public string $metricId,
        public string $commitSha,
        public string $ref,
        public DateTimeImmutable $createdAt,
    ) {
    }
}

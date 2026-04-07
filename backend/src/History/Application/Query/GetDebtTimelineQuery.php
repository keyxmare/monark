<?php

declare(strict_types=1);

namespace App\History\Application\Query;

use DateTimeImmutable;

final readonly class GetDebtTimelineQuery
{
    public function __construct(
        public string $projectId,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\History\Application\Command;

use DateTimeImmutable;

final readonly class BackfillProjectHistoryCommand
{
    public function __construct(
        public string $projectId,
        public DateTimeImmutable $since,
        public DateTimeImmutable $until,
        public int $intervalDays,
    ) {
    }
}

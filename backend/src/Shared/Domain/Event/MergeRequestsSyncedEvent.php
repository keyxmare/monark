<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

final readonly class MergeRequestsSyncedEvent
{
    public function __construct(
        public string $projectId,
        public int $created,
        public int $updated,
    ) {
    }
}

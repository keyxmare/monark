<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

final readonly class ProjectSyncCompletedEvent
{
    public function __construct(
        public string $projectId,
        public string $syncJobId,
    ) {
    }
}

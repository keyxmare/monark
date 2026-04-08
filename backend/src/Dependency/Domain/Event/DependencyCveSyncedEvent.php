<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class DependencyCveSyncedEvent
{
    public function __construct(
        public string $projectId,
        public int $vulnerabilitiesFound,
    ) {
    }
}

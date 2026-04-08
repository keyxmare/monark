<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

final readonly class ProjectCoverageFetchedEvent
{
    public function __construct(
        public string $projectId,
        public string $syncId,
        public string $projectName,
        public ?float $coveragePercent,
    ) {
    }
}

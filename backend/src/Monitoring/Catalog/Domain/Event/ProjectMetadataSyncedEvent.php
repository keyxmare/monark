<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Event;

final readonly class ProjectMetadataSyncedEvent
{
    /**
     * @param list<string> $changedFields
     */
    public function __construct(
        public string $projectId,
        public array $changedFields,
    ) {
    }
}

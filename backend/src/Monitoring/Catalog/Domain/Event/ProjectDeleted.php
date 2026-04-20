<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Event;

final readonly class ProjectDeleted
{
    public function __construct(
        public string $projectId,
    ) {
    }
}

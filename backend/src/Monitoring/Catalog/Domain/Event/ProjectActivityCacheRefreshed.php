<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Event;

final readonly class ProjectActivityCacheRefreshed
{
    public function __construct(
        public string $projectId,
    ) {
    }
}

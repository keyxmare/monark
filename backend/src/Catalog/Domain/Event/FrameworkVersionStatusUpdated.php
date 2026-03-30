<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

final readonly class FrameworkVersionStatusUpdated
{
    public function __construct(
        public string $frameworkId,
        public string $projectId,
        public string $framework,
        public ?string $latestLts,
        public ?string $maintenanceStatus,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

final readonly class TechStackVersionStatusUpdated
{
    public function __construct(
        public string $techStackId,
        public string $projectId,
        public string $framework,
        public ?string $latestLts,
        public ?string $maintenanceStatus,
    ) {
    }
}

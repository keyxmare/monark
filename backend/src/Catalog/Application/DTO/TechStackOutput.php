<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class TechStackOutput
{
    public function __construct(
        public string $id,
        public string $language,
        public string $framework,
        public string $version,
        public string $frameworkVersion,
        public string $detectedAt,
        public string $projectId,
        public string $createdAt,
        public ?string $latestLts = null,
        public ?string $ltsGap = null,
        public ?string $maintenanceStatus = null,
        public ?string $eolDate = null,
        public ?string $versionSyncedAt = null,
    ) {
    }
}

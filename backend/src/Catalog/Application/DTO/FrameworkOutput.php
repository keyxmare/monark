<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class FrameworkOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
        public string $detectedAt,
        public string $languageId,
        public string $languageName,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $latestLts = null,
        public ?string $ltsGap = null,
        public ?string $maintenanceStatus = null,
        public ?string $eolDate = null,
        public ?string $versionSyncedAt = null,
    ) {
    }
}

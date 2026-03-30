<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class LanguageOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
        public string $detectedAt,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $eolDate = null,
        public ?string $maintenanceStatus = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

final readonly class LanguageStatusUpdated
{
    public function __construct(
        public string $languageId,
        public string $projectId,
        public string $language,
        public ?string $maintenanceStatus,
    ) {
    }
}

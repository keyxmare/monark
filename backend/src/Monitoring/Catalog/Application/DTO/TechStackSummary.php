<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\DTO;

final readonly class TechStackSummary
{
    public function __construct(
        public string $language,
        public ?string $framework,
        public ?string $version,
    ) {
    }
}

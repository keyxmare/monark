<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class TechStackSummaryDTO
{
    public function __construct(
        public string $language,
        public ?string $framework,
    ) {
    }
}

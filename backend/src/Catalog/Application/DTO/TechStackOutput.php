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
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Coverage\Application\DTO;

final readonly class CoverageProjectOutput
{
    public function __construct(
        public string $projectId,
        public string $projectName,
        public string $projectSlug,
        public ?float $coveragePercent,
        public ?float $trend,
        public ?string $source,
        public ?string $commitHash,
        public ?string $ref,
        public ?string $syncedAt,
    ) {}
}

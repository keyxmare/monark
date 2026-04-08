<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class BuildMetricOutput
{
    public function __construct(
        public string $id,
        public string $projectId,
        public string $commitSha,
        public string $ref,
        public ?float $backendCoverage,
        public ?float $frontendCoverage,
        public ?float $mutationScore,
        public string $createdAt,
    ) {
    }
}

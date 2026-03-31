<?php

declare(strict_types=1);

namespace App\Coverage\Domain\ValueObject;

final readonly class CoverageResult
{
    public function __construct(
        public float $coveragePercent,
        public string $commitHash,
        public string $ref,
        public ?string $pipelineId,
    ) {
    }
}

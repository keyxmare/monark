<?php

declare(strict_types=1);

namespace App\Coverage\Domain\ValueObject;

final readonly class JobCoverage
{
    public function __construct(
        public string $name,
        public float $percent,
    ) {
    }
}

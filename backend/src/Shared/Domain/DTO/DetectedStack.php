<?php

declare(strict_types=1);

namespace App\Shared\Domain\DTO;

final readonly class DetectedStack
{
    public function __construct(
        public string $language,
        public string $framework,
        public string $version,
        public string $frameworkVersion,
    ) {
    }
}

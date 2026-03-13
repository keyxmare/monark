<?php

declare(strict_types=1);

namespace App\Shared\Domain\DTO;

final readonly class ScanResult
{
    /**
     * @param list<DetectedStack> $stacks
     * @param list<DetectedDependency> $dependencies
     */
    public function __construct(
        public array $stacks,
        public array $dependencies,
    ) {
    }
}

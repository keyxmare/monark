<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

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

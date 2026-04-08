<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class ScanResultOutput
{
    /**
     * @param list<array{language: string, framework: string, version: string, frameworkVersion: string}> $stacks
     * @param list<array{name: string, version: string, packageManager: string, type: string}> $dependencies
     */
    public function __construct(
        public int $stacksDetected,
        public int $dependenciesDetected,
        public array $stacks,
        public array $dependencies,
    ) {
    }
}

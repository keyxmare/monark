<?php

declare(strict_types=1);

namespace App\Shared\Domain\DTO;

final readonly class DependencyReadDTO
{
    /**
     * @param list<VulnerabilityReadDTO> $vulnerabilities
     */
    public function __construct(
        public string $name,
        public string $currentVersion,
        public string $latestVersion,
        public string $packageManager,
        public bool $isOutdated,
        public array $vulnerabilities = [],
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\DTO;

final readonly class RuntimeSummary
{
    public function __construct(
        public string $name,
        public string $productKey,
        public ?string $minVersion,
        public ?string $latestVersion,
        public ?string $ltsVersion,
    ) {
    }
}

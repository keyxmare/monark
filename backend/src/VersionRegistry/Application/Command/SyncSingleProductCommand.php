<?php

declare(strict_types=1);

namespace App\VersionRegistry\Application\Command;

final readonly class SyncSingleProductCommand
{
    public function __construct(
        public string $productName,
        public ?string $packageManager,
        public string $resolverSource,
        public ?string $syncId = null,
        public int $index = 0,
        public int $total = 0,
    ) {
    }
}

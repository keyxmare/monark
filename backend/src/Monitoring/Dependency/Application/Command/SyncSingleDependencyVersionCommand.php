<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\Command;

final readonly class SyncSingleDependencyVersionCommand
{
    public function __construct(
        public string $packageName,
        public string $packageManager,
        public ?string $syncId = null,
        public int $index = 0,
        public int $total = 0,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Sync\Application\Command;

final readonly class GlobalSyncCommand
{
    public function __construct(
        public string $syncId,
    ) {
    }
}

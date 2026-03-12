<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

final readonly class SyncMergeRequestsCommand
{
    public function __construct(
        public string $projectId,
        public bool $force = false,
        public ?string $syncJobId = null,
    ) {
    }
}

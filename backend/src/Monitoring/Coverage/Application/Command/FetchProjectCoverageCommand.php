<?php

declare(strict_types=1);

namespace App\Monitoring\Coverage\Application\Command;

final readonly class FetchProjectCoverageCommand
{
    public function __construct(
        public string $projectId,
        public string $syncId,
    ) {
    }
}

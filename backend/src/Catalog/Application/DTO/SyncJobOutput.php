<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class SyncJobOutput
{
    public function __construct(
        public int $projectsCount,
        public string $startedAt,
    ) {
    }
}

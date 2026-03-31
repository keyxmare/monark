<?php

declare(strict_types=1);

namespace App\Sync\Application\DTO;

final readonly class GlobalSyncJobOutput
{
    public function __construct(
        public string $syncId,
        public string $status,
        public int $currentStep,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Activity\Application\Command;

final readonly class UpdateSyncTaskStatusCommand
{
    public function __construct(
        public string $id,
        public string $status,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\Domain\DTO\ScanResult;

final readonly class ProjectScannedEvent
{
    public function __construct(
        public string $projectId,
        public ScanResult $scanResult,
    ) {
    }
}

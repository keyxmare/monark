<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Event;

use App\Hub\Shared\Domain\DTO\ScanResult;

final readonly class ProjectScannedEvent
{
    public function __construct(
        public string $projectId,
        public ScanResult $scanResult,
    ) {
    }
}

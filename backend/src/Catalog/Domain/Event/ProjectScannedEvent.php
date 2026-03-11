<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

use App\Catalog\Domain\Model\ScanResult;

final readonly class ProjectScannedEvent
{
    public function __construct(
        public string $projectId,
        public ScanResult $scanResult,
    ) {
    }
}

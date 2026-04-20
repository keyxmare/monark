<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Port;

use App\Hub\Shared\Domain\DTO\ScanResult;
use App\Monitoring\Catalog\Domain\Model\Project;

interface ProjectScannerInterface
{
    public function scan(Project $project): ScanResult;
}

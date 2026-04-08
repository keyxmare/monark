<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Port;

use App\Catalog\Domain\Model\Project;
use App\Shared\Domain\DTO\ScanResult;

interface ProjectScannerInterface
{
    public function scan(Project $project): ScanResult;
}

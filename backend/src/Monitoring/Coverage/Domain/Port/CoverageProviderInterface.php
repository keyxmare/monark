<?php

declare(strict_types=1);

namespace App\Monitoring\Coverage\Domain\Port;

use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\Catalog\Domain\Model\ProviderType;
use App\Monitoring\Coverage\Domain\ValueObject\CoverageResult;

interface CoverageProviderInterface
{
    public function supports(ProviderType $type): bool;

    public function fetchCoverage(Project $project): ?CoverageResult;
}

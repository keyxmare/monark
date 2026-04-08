<?php

declare(strict_types=1);

namespace App\Coverage\Domain\Port;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\ValueObject\CoverageResult;

interface CoverageProviderInterface
{
    public function supports(ProviderType $type): bool;

    public function fetchCoverage(Project $project): ?CoverageResult;
}

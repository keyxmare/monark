<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Service\Strategy;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Dependency\Domain\ValueObject\SemanticVersion;

interface VersionStrategyInterface
{
    public function supports(PackageManager $manager): bool;

    public function isOutdated(SemanticVersion $current, SemanticVersion $latest): bool;
}

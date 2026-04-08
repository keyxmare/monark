<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Strategy;

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;

interface VersionStrategyInterface
{
    public function supports(PackageManager $manager): bool;

    public function isOutdated(SemanticVersion $current, SemanticVersion $latest): bool;
}

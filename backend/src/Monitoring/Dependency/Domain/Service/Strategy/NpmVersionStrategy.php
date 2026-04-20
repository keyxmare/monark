<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Service\Strategy;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Dependency\Domain\ValueObject\SemanticVersion;
use Override;

final readonly class NpmVersionStrategy implements VersionStrategyInterface
{
    private const int MINOR_GAP_THRESHOLD = 2;

    #[Override]
    public function supports(PackageManager $manager): bool
    {
        return $manager === PackageManager::Npm;
    }

    #[Override]
    public function isOutdated(SemanticVersion $current, SemanticVersion $latest): bool
    {
        if ($current->getMajorGap($latest) > 0 && $latest->isNewerThan($current)) {
            return true;
        }

        if ($current->major === $latest->major && $current->getMinorGap($latest) >= self::MINOR_GAP_THRESHOLD) {
            return true;
        }

        return false;
    }
}

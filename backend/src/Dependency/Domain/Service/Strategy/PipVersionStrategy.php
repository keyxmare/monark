<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Strategy;

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;
use Override;

final readonly class PipVersionStrategy implements VersionStrategyInterface
{
    private const int MINOR_GAP_THRESHOLD = 3;

    #[Override]
    public function supports(PackageManager $manager): bool
    {
        return $manager === PackageManager::Pip;
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

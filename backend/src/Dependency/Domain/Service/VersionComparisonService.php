<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service;

use App\Dependency\Domain\Service\Strategy\VersionStrategyInterface;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;
use RuntimeException;

final readonly class VersionComparisonService
{
    /** @param iterable<VersionStrategyInterface> $strategies */
    public function __construct(
        private iterable $strategies,
    ) {
    }

    public function isOutdated(SemanticVersion $current, SemanticVersion $latest, PackageManager $manager): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($manager)) {
                return $strategy->isOutdated($current, $latest);
            }
        }

        throw new RuntimeException(\sprintf('No version strategy found for package manager "%s"', $manager->value));
    }
}

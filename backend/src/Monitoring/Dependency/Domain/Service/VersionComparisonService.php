<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Service;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Dependency\Domain\Service\Strategy\VersionStrategyInterface;
use App\Monitoring\Dependency\Domain\ValueObject\SemanticVersion;
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

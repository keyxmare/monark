<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;
use Override;

final readonly class HasVersionGapAboveSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private string $gapType,
        private int $threshold,
    ) {
    }

    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        $current = $candidate->getSemanticCurrentVersion();
        $latest = $candidate->getSemanticLatestVersion();

        if ($current === null || $latest === null) {
            return false;
        }

        $gap = match ($this->gapType) {
            'major' => $current->getMajorGap($latest),
            'minor' => $current->getMinorGap($latest),
            'patch' => $current->getPatchGap($latest),
            default => 0,
        };

        return $gap > $this->threshold;
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create();
    }
}

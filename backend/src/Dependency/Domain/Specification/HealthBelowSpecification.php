<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Service\DependencyHealthCalculator;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;
use Override;

final readonly class HealthBelowSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private int $threshold,
        private DependencyHealthCalculator $calculator,
    ) {
    }

    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $this->calculator->calculate($candidate)->getScore() < $this->threshold;
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create();
    }
}

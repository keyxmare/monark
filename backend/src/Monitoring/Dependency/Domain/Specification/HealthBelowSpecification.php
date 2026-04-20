<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Specification;

use App\Hub\Shared\Domain\Specification\QueryableSpecificationInterface;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use App\Monitoring\Dependency\Domain\Service\DependencyHealthCalculator;
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

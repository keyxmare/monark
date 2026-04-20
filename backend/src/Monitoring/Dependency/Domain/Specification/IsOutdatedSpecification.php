<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Specification;

use App\Hub\Shared\Domain\Specification\QueryableSpecificationInterface;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use Doctrine\Common\Collections\Criteria;
use Override;

final readonly class IsOutdatedSpecification implements QueryableSpecificationInterface
{
    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $candidate->isOutdated();
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create()->andWhere(Criteria::expr()->eq('isOutdated', true));
    }
}

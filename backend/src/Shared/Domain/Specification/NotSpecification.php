<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;
use Override;

final readonly class NotSpecification implements QueryableSpecificationInterface
{
    public function __construct(private SpecificationInterface $spec)
    {
    }

    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return !$this->spec->isSatisfiedBy($candidate);
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        $criteria = Criteria::create();

        if ($this->spec instanceof QueryableSpecificationInterface) {
            $expr = $this->spec->toDoctrineCriteria()->getWhereExpression();

            if ($expr !== null) {
                $criteria->andWhere(Criteria::expr()->not($expr));
            }
        }

        return $criteria;
    }
}

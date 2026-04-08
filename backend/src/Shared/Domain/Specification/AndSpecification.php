<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;
use Override;

final readonly class AndSpecification implements QueryableSpecificationInterface
{
    /** @param list<SpecificationInterface> $specs */
    public function __construct(private array $specs)
    {
    }

    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        foreach ($this->specs as $spec) {
            if (!$spec->isSatisfiedBy($candidate)) {
                return false;
            }
        }

        return true;
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        $criteria = Criteria::create();

        foreach ($this->specs as $spec) {
            if ($spec instanceof QueryableSpecificationInterface) {
                $expr = $spec->toDoctrineCriteria()->getWhereExpression();

                if ($expr !== null) {
                    $criteria->andWhere($expr);
                }
            }
        }

        return $criteria;
    }
}

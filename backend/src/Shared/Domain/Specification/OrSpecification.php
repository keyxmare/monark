<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Override;

final readonly class OrSpecification implements QueryableSpecificationInterface
{
    /** @param list<SpecificationInterface> $specs */
    public function __construct(private array $specs)
    {
    }

    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        foreach ($this->specs as $spec) {
            if ($spec->isSatisfiedBy($candidate)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        $expressions = [];

        foreach ($this->specs as $spec) {
            if ($spec instanceof QueryableSpecificationInterface) {
                $expr = $spec->toDoctrineCriteria()->getWhereExpression();

                if ($expr !== null) {
                    $expressions[] = $expr;
                }
            }
        }

        $criteria = Criteria::create();

        if ($expressions !== []) {
            $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $expressions));
        }

        return $criteria;
    }
}

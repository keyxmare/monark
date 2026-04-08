<?php

declare(strict_types=1);

use App\Shared\Domain\Specification\AndSpecification;
use App\Shared\Domain\Specification\NotSpecification;
use App\Shared\Domain\Specification\OrSpecification;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;

function createQueryableSpec(bool $satisfies, string $field, mixed $value): QueryableSpecificationInterface
{
    return new class ($satisfies, $field, $value) implements QueryableSpecificationInterface {
        public function __construct(
            private readonly bool $satisfies,
            private readonly string $field,
            private readonly mixed $value,
        ) {
        }

        public function isSatisfiedBy(mixed $candidate): bool
        {
            return $this->satisfies;
        }

        public function toDoctrineCriteria(): Criteria
        {
            return Criteria::create()->andWhere(Criteria::expr()->eq($this->field, $this->value));
        }
    };
}

describe('QueryableSpecificationInterface composites', function () {
    describe('AndSpecification', function () {
        it('combines criteria with AND', function () {
            $specA = \createQueryableSpec(true, 'status', 'active');
            $specB = \createQueryableSpec(true, 'type', 'runtime');

            $and = new AndSpecification([$specA, $specB]);
            $criteria = $and->toDoctrineCriteria();

            expect($criteria)->toBeInstanceOf(Criteria::class)
                ->and($criteria->getWhereExpression())->toBeInstanceOf(CompositeExpression::class);
        });

        it('satisfiedBy returns true only when all match', function () {
            $specA = \createQueryableSpec(true, 'a', 1);
            $specB = \createQueryableSpec(false, 'b', 2);

            $and = new AndSpecification([$specA, $specB]);

            expect($and->isSatisfiedBy(new \stdClass()))->toBeFalse();
        });
    });

    describe('OrSpecification', function () {
        it('combines criteria with OR', function () {
            $specA = \createQueryableSpec(true, 'status', 'active');
            $specB = \createQueryableSpec(true, 'status', 'pending');

            $or = new OrSpecification([$specA, $specB]);
            $criteria = $or->toDoctrineCriteria();

            expect($criteria)->toBeInstanceOf(Criteria::class)
                ->and($criteria->getWhereExpression())->toBeInstanceOf(CompositeExpression::class);
        });

        it('satisfiedBy returns true when any match', function () {
            $specA = \createQueryableSpec(false, 'a', 1);
            $specB = \createQueryableSpec(true, 'b', 2);

            $or = new OrSpecification([$specA, $specB]);

            expect($or->isSatisfiedBy(new \stdClass()))->toBeTrue();
        });
    });

    describe('NotSpecification', function () {
        it('negates inner spec', function () {
            $spec = \createQueryableSpec(true, 'a', 1);
            $not = new NotSpecification($spec);

            expect($not->isSatisfiedBy(new \stdClass()))->toBeFalse();
        });

        it('generates criteria', function () {
            $spec = \createQueryableSpec(true, 'status', 'deprecated');
            $not = new NotSpecification($spec);
            $criteria = $not->toDoctrineCriteria();

            expect($criteria)->toBeInstanceOf(Criteria::class);
        });
    });

    describe('composition', function () {
        it('Not(Not(A)) is equivalent to A', function () {
            $spec = \createQueryableSpec(true, 'a', 1);
            $doubleNot = new NotSpecification(new NotSpecification($spec));

            expect($doubleNot->isSatisfiedBy(new \stdClass()))->toBe($spec->isSatisfiedBy(new \stdClass()));
        });
    });
});

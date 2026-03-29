<?php

declare(strict_types=1);

use App\Shared\Domain\Specification\AndSpecification;
use App\Shared\Domain\Specification\NotSpecification;
use App\Shared\Domain\Specification\OrSpecification;
use App\Shared\Domain\Specification\SpecificationInterface;

function alwaysTrue(): SpecificationInterface
{
    return new class () implements SpecificationInterface {
        public function isSatisfiedBy(mixed $candidate): bool
        {
            return true;
        }
    };
}

function alwaysFalse(): SpecificationInterface
{
    return new class () implements SpecificationInterface {
        public function isSatisfiedBy(mixed $candidate): bool
        {
            return false;
        }
    };
}

describe('AndSpecification', function () {
    it('returns true when all specs are satisfied', function () {
        $spec = new AndSpecification([\alwaysTrue(), \alwaysTrue()]);
        expect($spec->isSatisfiedBy('anything'))->toBeTrue();
    });

    it('returns false when any spec is not satisfied', function () {
        $spec = new AndSpecification([\alwaysTrue(), \alwaysFalse()]);
        expect($spec->isSatisfiedBy('anything'))->toBeFalse();
    });

    it('returns true for empty list', function () {
        $spec = new AndSpecification([]);
        expect($spec->isSatisfiedBy('anything'))->toBeTrue();
    });

    it('returns false when all specs are not satisfied', function () {
        $spec = new AndSpecification([\alwaysFalse(), \alwaysFalse()]);
        expect($spec->isSatisfiedBy('anything'))->toBeFalse();
    });
});

describe('OrSpecification', function () {
    it('returns true when any spec is satisfied', function () {
        $spec = new OrSpecification([\alwaysFalse(), \alwaysTrue()]);
        expect($spec->isSatisfiedBy('anything'))->toBeTrue();
    });

    it('returns false when no spec is satisfied', function () {
        $spec = new OrSpecification([\alwaysFalse(), \alwaysFalse()]);
        expect($spec->isSatisfiedBy('anything'))->toBeFalse();
    });

    it('returns false for empty list', function () {
        $spec = new OrSpecification([]);
        expect($spec->isSatisfiedBy('anything'))->toBeFalse();
    });

    it('returns true when all specs are satisfied', function () {
        $spec = new OrSpecification([\alwaysTrue(), \alwaysTrue()]);
        expect($spec->isSatisfiedBy('anything'))->toBeTrue();
    });
});

describe('NotSpecification', function () {
    it('negates a true spec', function () {
        $spec = new NotSpecification(\alwaysTrue());
        expect($spec->isSatisfiedBy('anything'))->toBeFalse();
    });

    it('negates a false spec', function () {
        $spec = new NotSpecification(\alwaysFalse());
        expect($spec->isSatisfiedBy('anything'))->toBeTrue();
    });
});

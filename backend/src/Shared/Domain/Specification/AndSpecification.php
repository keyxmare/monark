<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

final readonly class AndSpecification implements SpecificationInterface
{
    /** @param list<SpecificationInterface> $specs */
    public function __construct(private array $specs)
    {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        foreach ($this->specs as $spec) {
            if (!$spec->isSatisfiedBy($candidate)) {
                return false;
            }
        }

        return true;
    }
}

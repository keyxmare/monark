<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

final readonly class NotSpecification implements SpecificationInterface
{
    public function __construct(private SpecificationInterface $spec)
    {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return !$this->spec->isSatisfiedBy($candidate);
    }
}

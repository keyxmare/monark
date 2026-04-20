<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Specification;

interface SpecificationInterface
{
    public function isSatisfiedBy(mixed $candidate): bool;
}

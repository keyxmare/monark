<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Shared\Domain\Specification\SpecificationInterface;

final readonly class IsDeprecatedSpecification implements SpecificationInterface
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $candidate->getRegistryStatus() === RegistryStatus::Deprecated;
    }
}

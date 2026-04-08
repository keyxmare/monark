<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;
use Override;

final readonly class IsDeprecatedSpecification implements QueryableSpecificationInterface
{
    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $candidate->getRegistryStatus() === RegistryStatus::Deprecated;
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create()->andWhere(Criteria::expr()->eq('registryStatus', RegistryStatus::Deprecated));
    }
}

<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Specification;

use App\Hub\Shared\Domain\Specification\QueryableSpecificationInterface;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use Doctrine\Common\Collections\Criteria;
use Override;
use Symfony\Component\Uid\Uuid;

final readonly class BelongsToProjectSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private Uuid $projectId,
    ) {
    }

    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $candidate->getProjectId()->equals($this->projectId);
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create()->andWhere(Criteria::expr()->eq('projectId', $this->projectId));
    }
}

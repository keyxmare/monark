<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Specification;

use App\Hub\Shared\Domain\Specification\QueryableSpecificationInterface;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use DateTimeImmutable;
use Doctrine\Common\Collections\Criteria;
use Override;

final readonly class IsStaleSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private int $days,
    ) {
    }

    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        $threshold = new DateTimeImmutable(\sprintf('-%d days', $this->days));

        return $candidate->getUpdatedAt() < $threshold;
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        $threshold = new DateTimeImmutable(\sprintf('-%d days', $this->days));

        return Criteria::create()->andWhere(Criteria::expr()->lt('updatedAt', $threshold));
    }
}

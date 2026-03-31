<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Shared\Domain\ValueObject\Severity;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;
use Override;

final readonly class HasSeverityAboveSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private Severity $threshold,
    ) {
    }

    #[Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        foreach ($candidate->getVulnerabilities() as $vulnerability) {
            if ($vulnerability->getSeverity()->isHigherThan($this->threshold) || $vulnerability->getSeverity() === $this->threshold) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create();
    }
}

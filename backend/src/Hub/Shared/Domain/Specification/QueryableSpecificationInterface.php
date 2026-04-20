<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;

interface QueryableSpecificationInterface extends SpecificationInterface
{
    public function toDoctrineCriteria(): Criteria;
}

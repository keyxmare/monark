<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface CommitCounterInterface
{
    public function countDistinctSince(int $days): int;
}

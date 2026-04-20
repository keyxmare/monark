<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface ProjectCounterInterface
{
    public function countTracked(): int;
}

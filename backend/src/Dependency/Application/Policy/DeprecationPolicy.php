<?php

declare(strict_types=1);

namespace App\Dependency\Application\Policy;

use App\Dependency\Domain\Model\RegistryStatus;

final readonly class DeprecationPolicy
{
    public function __construct(
        private int $threshold = 3,
    ) {
    }

    public function shouldDeprecate(int $notFoundCount): bool
    {
        return $notFoundCount >= $this->threshold;
    }

    public function resolveStatus(int $notFoundCount): RegistryStatus
    {
        return $this->shouldDeprecate($notFoundCount)
            ? RegistryStatus::Deprecated
            : RegistryStatus::NotFound;
    }
}

<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Event;

final readonly class DependencyVersionSynced
{
    public function __construct(
        public string $packageName,
        public string $packageManager,
    ) {
    }
}

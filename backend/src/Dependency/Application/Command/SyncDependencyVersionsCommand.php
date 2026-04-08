<?php

declare(strict_types=1);

namespace App\Dependency\Application\Command;

final readonly class SyncDependencyVersionsCommand
{
    /** @param list<string>|null $packageNames */
    public function __construct(
        public ?array $packageNames = null,
        public ?string $syncId = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

final readonly class SyncAllProjectsCommand
{
    /**
     * @param list<string>|null $projectIds
     */
    public function __construct(
        public ?string $providerId = null,
        public bool $force = false,
        public ?array $projectIds = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Monitoring\VersionRegistry\Application\Command;

use App\Monitoring\VersionRegistry\Domain\Model\ResolverSource;

final readonly class SyncProductVersionsCommand
{
    /** @param list<string>|null $productNames */
    public function __construct(
        public ?array $productNames = null,
        public ?string $syncId = null,
        public ?ResolverSource $resolverSource = null,
    ) {
    }
}

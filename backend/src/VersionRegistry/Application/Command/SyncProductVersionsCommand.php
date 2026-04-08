<?php

declare(strict_types=1);

namespace App\VersionRegistry\Application\Command;

final readonly class SyncProductVersionsCommand
{
    /** @param list<string>|null $productNames */
    public function __construct(
        public ?array $productNames = null,
        public ?string $syncId = null,
    ) {
    }
}

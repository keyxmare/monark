<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\Domain\ValueObject\PackageManager;

final readonly class ProductVersionsSyncedEvent
{
    /**
     * @param list<array{version: string, eolDate: ?string, isLts: bool}> $eolCycles
     */
    public function __construct(
        public string $productName,
        public ?PackageManager $packageManager,
        public ?string $latestVersion,
        public ?string $ltsVersion,
        public array $eolCycles = [],
    ) {
    }
}

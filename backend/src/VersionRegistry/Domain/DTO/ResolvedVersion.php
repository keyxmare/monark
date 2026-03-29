<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\DTO;

use DateTimeImmutable;

final readonly class ResolvedVersion
{
    public function __construct(
        public string $version,
        public ?DateTimeImmutable $releaseDate = null,
        public bool $isLts = false,
        public bool $isLatest = false,
        public ?string $eolDate = null,
    ) {
    }
}

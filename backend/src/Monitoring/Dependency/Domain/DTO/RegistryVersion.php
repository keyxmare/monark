<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\DTO;

use DateTimeImmutable;

final readonly class RegistryVersion
{
    public function __construct(
        public string $version,
        public ?DateTimeImmutable $releaseDate = null,
        public bool $isLatest = false,
    ) {
    }
}

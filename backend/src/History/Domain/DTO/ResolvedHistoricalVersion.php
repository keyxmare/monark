<?php

declare(strict_types=1);

namespace App\History\Domain\DTO;

final readonly class ResolvedHistoricalVersion
{
    public function __construct(
        public ?string $latestVersion,
        public ?string $ltsVersion,
    ) {
    }

    public static function empty(): self
    {
        return new self(null, null);
    }
}

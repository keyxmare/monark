<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\ValueObject;

use App\Dependency\Domain\ValueObject\SemanticVersion;
use InvalidArgumentException;

final readonly class ResolvedSemanticVersion
{
    public function __construct(
        public SemanticVersion $version,
        public bool $isLts,
        public bool $isLatest,
    ) {
    }

    public static function fromString(string $version, bool $isLts, bool $isLatest): self
    {
        return new self(
            version: SemanticVersion::parse($version),
            isLts: $isLts,
            isLatest: $isLatest,
        );
    }

    public static function tryFromString(string $version, bool $isLts = false, bool $isLatest = false): ?self
    {
        try {
            return self::fromString($version, $isLts, $isLatest);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function isLatestLts(): bool
    {
        return $this->isLts && $this->isLatest;
    }
}

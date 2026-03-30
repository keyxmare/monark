<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class CveId implements Stringable, JsonSerializable
{
    private function __construct(
        private int $year,
        private int $sequence,
    ) {
    }

    public static function fromString(string $value): self
    {
        if (!\preg_match('/^CVE-(\d{4})-(\d{4,})$/', \trim($value), $matches)) {
            throw new InvalidArgumentException(\sprintf('Invalid CVE identifier: "%s"', $value));
        }

        return new self(
            year: (int) $matches[1],
            sequence: (int) $matches[2],
        );
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function equals(self $other): bool
    {
        return $this->year === $other->year && $this->sequence === $other->sequence;
    }

    public function __toString(): string
    {
        return \sprintf('CVE-%d-%d', $this->year, $this->sequence);
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}

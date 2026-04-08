<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class SemanticVersion implements Stringable, JsonSerializable
{
    private function __construct(
        public int $major,
        public int $minor,
        public int $patch,
        public ?string $preRelease = null,
    ) {
    }

    public static function parse(string $version): self
    {
        $pattern = '/^v?(\d+)\.(\d+)(?:\.(\d+))?(?:-([a-zA-Z0-9.]+))?$/';

        if (!\preg_match($pattern, \trim($version), $matches)) {
            throw new InvalidArgumentException(\sprintf('Invalid semantic version: "%s"', $version));
        }

        return new self(
            major: (int) $matches[1],
            minor: (int) $matches[2],
            patch: ($matches[3] ?? '') !== '' ? (int) $matches[3] : 0,
            preRelease: ($matches[4] ?? '') !== '' ? $matches[4] : null,
        );
    }

    public function isNewerThan(self $other): bool
    {
        return match (true) {
            $this->major !== $other->major => $this->major > $other->major,
            $this->minor !== $other->minor => $this->minor > $other->minor,
            $this->patch !== $other->patch => $this->patch > $other->patch,
            default => $this->comparePreRelease($other) > 0,
        };
    }

    public function isCompatibleWith(self $other): bool
    {
        return $this->major === $other->major;
    }

    public function getMajorGap(self $other): int
    {
        return \abs($this->major - $other->major);
    }

    public function getMinorGap(self $other): int
    {
        return \abs($this->minor - $other->minor);
    }

    public function getPatchGap(self $other): int
    {
        return \abs($this->patch - $other->patch);
    }

    public function isPreRelease(): bool
    {
        return $this->preRelease !== null;
    }

    public function equals(self $other): bool
    {
        return $this->major === $other->major
            && $this->minor === $other->minor
            && $this->patch === $other->patch
            && $this->preRelease === $other->preRelease;
    }

    public function __toString(): string
    {
        $version = \sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);

        if ($this->preRelease !== null) {
            $version .= '-' . $this->preRelease;
        }

        return $version;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    private function comparePreRelease(self $other): int
    {
        if ($this->preRelease === $other->preRelease) {
            return 0;
        }

        if ($this->preRelease === null) {
            return 1;
        }

        if ($other->preRelease === null) {
            return -1;
        }

        $aParts = \explode('.', $this->preRelease);
        $bParts = \explode('.', $other->preRelease);
        $length = \max(\count($aParts), \count($bParts));

        for ($i = 0; $i < $length; $i++) {
            $aPart = $aParts[$i] ?? '';
            $bPart = $bParts[$i] ?? '';
            $aIsNum = \ctype_digit($aPart);
            $bIsNum = \ctype_digit($bPart);

            if ($aIsNum && $bIsNum) {
                $diff = (int) $aPart - (int) $bPart;
                if ($diff !== 0) {
                    return $diff;
                }
            } elseif ($aIsNum !== $bIsNum) {
                return $aIsNum ? -1 : 1;
            } else {
                $diff = \strcmp($aPart, $bPart);
                if ($diff !== 0) {
                    return $diff;
                }
            }
        }

        return 0;
    }
}

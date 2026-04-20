<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\ValueObject;

enum VersionLag: string
{
    case Major = 'major';
    case Minor = 'minor';
    case Patch = 'patch';
    case UpToDate = 'up_to_date';
    case Unknown = 'unknown';

    /**
     * Classify the gap between a currently-installed version and a reference target (e.g. latest LTS).
     * Returns Unknown when either side can't be parsed.
     */
    public static function classify(?string $current, ?string $reference): self
    {
        if ($current === null || $reference === null) {
            return self::Unknown;
        }

        $cur = self::parse($current);
        $ref = self::parse($reference);
        if ($cur === null || $ref === null) {
            return self::Unknown;
        }

        if ($cur[0] < $ref[0]) {
            return self::Major;
        }
        if ($cur[0] > $ref[0]) {
            return self::UpToDate;
        }
        if ($cur[1] < $ref[1]) {
            return self::Minor;
        }
        if ($cur[1] > $ref[1]) {
            return self::UpToDate;
        }
        if ($cur[2] < $ref[2]) {
            return self::Patch;
        }

        return self::UpToDate;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null [major, minor, patch] or null when the string is not semver-ish.
     */
    private static function parse(string $raw): ?array
    {
        $trimmed = \ltrim(\trim($raw), 'vV');
        $trimmed = \explode('-', $trimmed, 2)[0];
        $trimmed = \explode('+', $trimmed, 2)[0];
        if ($trimmed === '') {
            return null;
        }
        $parts = \explode('.', $trimmed);
        $numeric = [];
        foreach (\array_slice($parts, 0, 3) as $part) {
            if (!\preg_match('/^\d+/', $part, $m)) {
                return null;
            }
            $numeric[] = (int) $m[0];
        }
        while (\count($numeric) < 3) {
            $numeric[] = 0;
        }

        return [$numeric[0], $numeric[1], $numeric[2]];
    }
}

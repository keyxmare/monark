<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

enum MaintenanceStatus: string
{
    case Active = 'active';
    case Eol = 'eol';
    case Unknown = 'unknown';

    public static function fromString(?string $value): self
    {
        if ($value === null) {
            return self::Unknown;
        }

        return self::tryFrom($value) ?? self::Unknown;
    }
}

<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

enum RiskLevel: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case None = 'none';

    public function isAbove(self $other): bool
    {
        return $this->weight() > $other->weight();
    }

    public function weight(): int
    {
        return match ($this) {
            self::Critical => 5,
            self::High => 4,
            self::Medium => 3,
            self::Low => 2,
            self::None => 0,
        };
    }
}

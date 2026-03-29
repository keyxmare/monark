<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Model;

enum Severity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function isHigherThan(self $other): bool
    {
        return $this->weight() > $other->weight();
    }

    private function weight(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

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

    public static function fromCvssScore(float $score): self
    {
        return match (true) {
            $score >= 9.0 => self::Critical,
            $score >= 7.0 => self::High,
            $score >= 4.0 => self::Medium,
            default => self::Low,
        };
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

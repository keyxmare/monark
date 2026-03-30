<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

use App\Dependency\Domain\ValueObject\RiskLevel;
use InvalidArgumentException;

final readonly class TechStackHealth
{
    public function __construct(
        private int $score,
        private RiskLevel $riskLevel,
    ) {
        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException(\sprintf('TechStackHealth score must be between 0 and 100, got %d.', $score));
        }
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getRiskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    public function isHealthy(): bool
    {
        return $this->score >= 60;
    }
}

<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Severity;
use JsonSerializable;

final readonly class DependencyHealth implements JsonSerializable
{
    private const int WEIGHT_MAJOR_GAP = 40;
    private const int WEIGHT_MINOR_GAP = 20;
    private const int WEIGHT_PATCH_GAP = 5;
    private const int WEIGHT_DEPRECATED = 30;
    private const int WEIGHT_NOT_FOUND = 20;

    private function __construct(
        private int $score,
    ) {
    }

    /** @param list<Severity> $vulnerabilitySeverities */
    public static function calculate(
        int $majorGap,
        int $minorGap,
        int $patchGap,
        array $vulnerabilitySeverities,
        bool $isDeprecated,
        bool $isNotFound,
    ): self {
        $penalty = 0;
        $penalty += $majorGap * self::WEIGHT_MAJOR_GAP;
        $penalty += $minorGap * self::WEIGHT_MINOR_GAP;
        $penalty += $patchGap * self::WEIGHT_PATCH_GAP;

        foreach ($vulnerabilitySeverities as $severity) {
            $penalty += self::vulnerabilityWeight($severity);
        }

        if ($isDeprecated) {
            $penalty += self::WEIGHT_DEPRECATED;
        }

        if ($isNotFound) {
            $penalty += self::WEIGHT_NOT_FOUND;
        }

        return new self(\max(0, \min(100, 100 - $penalty)));
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function isHealthy(): bool
    {
        return $this->score > 70;
    }

    public function getRiskLevel(): RiskLevel
    {
        return match (true) {
            $this->score <= 20 => RiskLevel::Critical,
            $this->score <= 40 => RiskLevel::High,
            $this->score <= 60 => RiskLevel::Medium,
            $this->score <= 80 => RiskLevel::Low,
            default => RiskLevel::None,
        };
    }

    /** @return array{score: int, riskLevel: string, healthy: bool} */
    public function jsonSerialize(): array
    {
        return [
            'score' => $this->score,
            'riskLevel' => $this->getRiskLevel()->value,
            'healthy' => $this->isHealthy(),
        ];
    }

    private static function vulnerabilityWeight(Severity $severity): int
    {
        return match ($severity) {
            Severity::Critical => 50,
            Severity::High => 30,
            Severity::Medium => 15,
            Severity::Low => 5,
        };
    }
}

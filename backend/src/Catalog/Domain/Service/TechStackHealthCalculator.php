<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Service;

use App\Catalog\Domain\ValueObject\MaintenanceStatus;
use App\Catalog\Domain\ValueObject\TechStackHealth;
use App\Dependency\Domain\ValueObject\RiskLevel;
use App\Dependency\Domain\ValueObject\SemanticVersion;

final class TechStackHealthCalculator
{
    private const int PENALTY_EOL = 50;
    private const int PENALTY_MAJOR_GAP = 30;
    private const int PENALTY_MINOR_GAP_THRESHOLD = 3;
    private const int PENALTY_MINOR_GAP = 15;
    private const int SCORE_UNKNOWN = 80;

    public function calculate(
        SemanticVersion $current,
        SemanticVersion $latest,
        MaintenanceStatus $status,
    ): TechStackHealth {
        $score = 100;

        if ($status === MaintenanceStatus::Eol) {
            $score -= self::PENALTY_EOL;
        }

        $majorGap = $latest->getMajorGap($current);
        if ($majorGap > 0) {
            $score -= $majorGap * self::PENALTY_MAJOR_GAP;
        }

        if ($majorGap === 0) {
            $minorGap = $latest->getMinorGap($current);
            if ($minorGap >= self::PENALTY_MINOR_GAP_THRESHOLD) {
                $score -= self::PENALTY_MINOR_GAP;
            }
        }

        $score = \max(0, $score);

        return new TechStackHealth(score: $score, riskLevel: $this->scoreToRisk($score));
    }

    public function calculateUnknown(): TechStackHealth
    {
        return new TechStackHealth(score: self::SCORE_UNKNOWN, riskLevel: RiskLevel::None);
    }

    private function scoreToRisk(int $score): RiskLevel
    {
        return match (true) {
            $score < 30 => RiskLevel::Critical,
            $score < 50 => RiskLevel::High,
            $score < 70 => RiskLevel::Medium,
            $score < 90 => RiskLevel::Low,
            default => RiskLevel::None,
        };
    }
}

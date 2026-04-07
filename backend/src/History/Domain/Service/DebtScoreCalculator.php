<?php

declare(strict_types=1);

namespace App\History\Domain\Service;

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\History\Domain\Model\GapType;
use InvalidArgumentException;

final class DebtScoreCalculator
{
    public function determineGapType(string $current, ?string $latest): GapType
    {
        if ($latest === null || $latest === '') {
            return GapType::Unknown;
        }
        if ($current === $latest) {
            return GapType::None;
        }

        try {
            $cur = SemanticVersion::parse($current);
            $lat = SemanticVersion::parse($latest);
        } catch (InvalidArgumentException) {
            return GapType::Unknown;
        }

        return match (true) {
            $cur->getMajorGap($lat) > 0 => GapType::Major,
            $cur->getMinorGap($lat) > 0 => GapType::Minor,
            default => GapType::Patch,
        };
    }

    public function score(int $totalDeps, int $major, int $minor, int $patch, int $vulnerable, int $ltsGap): float
    {
        if ($totalDeps === 0) {
            return 0.0;
        }

        $weighted = ($major * 5.0) + ($minor * 2.0) + ($patch * 0.5) + ($vulnerable * 8.0) + ($ltsGap * 3.0);
        $score = $weighted / $totalDeps;

        return \round($score, 2);
    }
}

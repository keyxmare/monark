<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

use App\Shared\Domain\ValueObject\VulnerabilityStatus;
use DateTimeImmutable;
use Override;

final readonly class AgeHandler implements AssessmentHandlerInterface
{
    private const int STALE_DAYS_THRESHOLD = 30;

    #[Override]
    public function assess(array $vulnerabilities): array
    {
        $score = 0.0;
        $recommendations = [];
        $now = new DateTimeImmutable();
        $staleCount = 0;

        foreach ($vulnerabilities as $vuln) {
            if ($vuln['status'] === VulnerabilityStatus::Fixed) {
                continue;
            }

            $daysSinceDetection = (int) $now->diff($vuln['detectedAt'])->days;

            if ($daysSinceDetection > self::STALE_DAYS_THRESHOLD) {
                $score += \min(2.0, $daysSinceDetection / 30.0 * 0.5);
                $staleCount++;
            }
        }

        if ($staleCount > 0) {
            $recommendations[] = \sprintf('%d vulnerabilit%s unresolved for over %d days', $staleCount, $staleCount > 1 ? 'ies' : 'y', self::STALE_DAYS_THRESHOLD);
        }

        return ['score' => $score, 'recommendations' => $recommendations];
    }
}

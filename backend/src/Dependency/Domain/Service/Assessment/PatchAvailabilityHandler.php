<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

use App\Shared\Domain\ValueObject\VulnerabilityStatus;
use Override;

final readonly class PatchAvailabilityHandler implements AssessmentHandlerInterface
{
    #[Override]
    public function assess(array $vulnerabilities): array
    {
        $score = 0.0;
        $recommendations = [];
        $unpatchedCount = 0;

        foreach ($vulnerabilities as $vuln) {
            if ($vuln['status'] === VulnerabilityStatus::Fixed) {
                continue;
            }

            if ($vuln['hasPatch']) {
                $score -= 0.5;
            } else {
                $score += 1.0;
                $unpatchedCount++;
            }
        }

        if ($unpatchedCount > 0) {
            $recommendations[] = \sprintf('%d vulnerabilit%s ha%s no available patch — consider alternatives or workarounds', $unpatchedCount, $unpatchedCount > 1 ? 'ies' : 'y', $unpatchedCount > 1 ? 've' : 's');
        }

        return ['score' => \max(0.0, $score), 'recommendations' => $recommendations];
    }
}

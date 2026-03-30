<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use Override;

final readonly class SeverityHandler implements AssessmentHandlerInterface
{
    #[Override]
    public function assess(array $vulnerabilities): array
    {
        $score = 0.0;
        $recommendations = [];

        foreach ($vulnerabilities as $vuln) {
            if ($vuln['status'] === VulnerabilityStatus::Fixed) {
                continue;
            }

            $score += match ($vuln['severity']) {
                Severity::Critical => 8.0,
                Severity::High => 5.0,
                Severity::Medium => 2.0,
                Severity::Low => 0.5,
            };
        }

        $criticalCount = \count(\array_filter(
            $vulnerabilities,
            static fn (array $v) => $v['severity'] === Severity::Critical && $v['status'] !== VulnerabilityStatus::Fixed,
        ));

        if ($criticalCount > 0) {
            $recommendations[] = \sprintf('Resolve %d critical vulnerabilit%s immediately', $criticalCount, $criticalCount > 1 ? 'ies' : 'y');
        }

        return ['score' => $score, 'recommendations' => $recommendations];
    }
}

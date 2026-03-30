<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

use DateTimeImmutable;

interface AssessmentHandlerInterface
{
    /**
     * @param list<array{severity: \App\Dependency\Domain\Model\Severity, status: \App\Dependency\Domain\Model\VulnerabilityStatus, hasPatch: bool, detectedAt: DateTimeImmutable}> $vulnerabilities
     * @return array{score: float, recommendations: list<string>}
     */
    public function assess(array $vulnerabilities): array;
}

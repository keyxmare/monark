<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Service\Assessment;

use DateTimeImmutable;

interface AssessmentHandlerInterface
{
    /**
     * @param list<array{severity: \App\Hub\Shared\Domain\ValueObject\Severity, status: \App\Hub\Shared\Domain\ValueObject\VulnerabilityStatus, hasPatch: bool, detectedAt: DateTimeImmutable}> $vulnerabilities
     * @return array{score: float, recommendations: list<string>}
     */
    public function assess(array $vulnerabilities): array;
}

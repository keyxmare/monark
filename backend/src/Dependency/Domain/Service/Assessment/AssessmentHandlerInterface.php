<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

use DateTimeImmutable;

interface AssessmentHandlerInterface
{
    /**
     * @param list<array{severity: \App\Shared\Domain\ValueObject\Severity, status: \App\Shared\Domain\ValueObject\VulnerabilityStatus, hasPatch: bool, detectedAt: DateTimeImmutable}> $vulnerabilities
     * @return array{score: float, recommendations: list<string>}
     */
    public function assess(array $vulnerabilities): array;
}

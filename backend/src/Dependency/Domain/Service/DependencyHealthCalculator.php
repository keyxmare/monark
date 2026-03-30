<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\ValueObject\DependencyHealth;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use InvalidArgumentException;

final readonly class DependencyHealthCalculator
{
    public function calculate(Dependency $dependency): DependencyHealth
    {
        $majorGap = 0;
        $minorGap = 0;
        $patchGap = 0;

        try {
            $current = SemanticVersion::parse($dependency->getCurrentVersion());
            $latest = SemanticVersion::parse($dependency->getLatestVersion());
            $majorGap = $current->getMajorGap($latest);
            $minorGap = $current->getMinorGap($latest);
            $patchGap = $current->getPatchGap($latest);
        } catch (InvalidArgumentException) {
        }

        $severities = [];
        foreach ($dependency->getVulnerabilities() as $vuln) {
            $severities[] = $vuln->getSeverity();
        }

        return DependencyHealth::calculate(
            majorGap: $majorGap,
            minorGap: $minorGap,
            patchGap: $patchGap,
            vulnerabilitySeverities: $severities,
            isDeprecated: $dependency->getRegistryStatus() === RegistryStatus::Deprecated,
            isNotFound: $dependency->getRegistryStatus() === RegistryStatus::NotFound,
        );
    }
}

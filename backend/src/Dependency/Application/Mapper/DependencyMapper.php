<?php

declare(strict_types=1);

namespace App\Dependency\Application\Mapper;

use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Domain\Model\Dependency;
use DateTimeInterface;

final class DependencyMapper
{
    public static function toOutput(
        Dependency $dependency,
        ?string $currentVersionReleasedAt = null,
        ?string $latestVersionReleasedAt = null,
    ): DependencyOutput {
        return new DependencyOutput(
            id: $dependency->getId()->toRfc4122(),
            name: $dependency->getName(),
            currentVersion: $dependency->getCurrentVersion(),
            latestVersion: $dependency->getLatestVersion(),
            ltsVersion: $dependency->getLtsVersion(),
            packageManager: $dependency->getPackageManager()->value,
            type: $dependency->getType()->value,
            isOutdated: $dependency->isOutdated(),
            projectId: $dependency->getProjectId()->toRfc4122(),
            repositoryUrl: $dependency->getRepositoryUrl(),
            vulnerabilityCount: $dependency->getVulnerabilityCount(),
            registryStatus: $dependency->getRegistryStatus()->value,
            createdAt: $dependency->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $dependency->getUpdatedAt()->format(DateTimeInterface::ATOM),
            currentVersionReleasedAt: $currentVersionReleasedAt,
            latestVersionReleasedAt: $latestVersionReleasedAt,
        );
    }
}

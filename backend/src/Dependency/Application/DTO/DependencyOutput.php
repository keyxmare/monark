<?php

declare(strict_types=1);

namespace App\Dependency\Application\DTO;

use App\Dependency\Domain\Model\Dependency;
use DateTimeInterface;

final readonly class DependencyOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $currentVersion,
        public string $latestVersion,
        public string $ltsVersion,
        public string $packageManager,
        public string $type,
        public bool $isOutdated,
        public string $projectId,
        public ?string $repositoryUrl,
        public int $vulnerabilityCount,
        public string $createdAt,
        public string $updatedAt,
        public string $registryStatus = 'pending',
        public ?string $currentVersionReleasedAt = null,
        public ?string $latestVersionReleasedAt = null,
    ) {
    }

    public static function fromEntity(
        Dependency $dependency,
        ?string $currentVersionReleasedAt = null,
        ?string $latestVersionReleasedAt = null,
    ): self {
        return new self(
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

<?php

declare(strict_types=1);

namespace App\Dependency\Application\DTO;

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
}

<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Adapter;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Port\DependencyWriterPort;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

final readonly class DependencyWriterAdapter implements DependencyWriterPort
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    public function deleteByProjectId(Uuid $projectId): void
    {
        $this->dependencyRepository->deleteByProjectId($projectId);
    }

    public function createFromScan(
        string $name,
        string $currentVersion,
        string $packageManager,
        string $type,
        Uuid $projectId,
        ?string $repositoryUrl,
    ): void {
        $dependency = Dependency::create(
            name: $name,
            currentVersion: $currentVersion,
            latestVersion: $currentVersion,
            ltsVersion: $currentVersion,
            packageManager: PackageManager::from($packageManager),
            type: DependencyType::from($type),
            isOutdated: false,
            projectId: $projectId,
            repositoryUrl: $repositoryUrl,
        );

        $this->dependencyRepository->save($dependency);
    }
}

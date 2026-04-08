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

    public function upsertFromScan(
        string $name,
        string $currentVersion,
        string $packageManager,
        string $type,
        Uuid $projectId,
        ?string $repositoryUrl,
    ): void {
        $existing = $this->dependencyRepository->findByNameManagerAndProjectId($name, $packageManager, $projectId);

        if ($existing !== null) {
            $versionChanged = $existing->getCurrentVersion() !== $currentVersion;
            $existing->update(
                currentVersion: $currentVersion,
                type: DependencyType::from($type),
                repositoryUrl: $repositoryUrl,
                isOutdated: $versionChanged ? \version_compare($currentVersion, $existing->getLatestVersion(), '<') : null,
            );
            $this->dependencyRepository->save($existing);

            return;
        }

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

    public function removeStaleByProjectId(Uuid $projectId, array $scannedDeps): void
    {
        $existing = $this->dependencyRepository->findByProjectId($projectId, 1, 10000);

        $scannedKeys = [];
        foreach ($scannedDeps as $dep) {
            $scannedKeys[$dep['name'] . '|' . $dep['packageManager']] = true;
        }

        foreach ($existing as $dep) {
            $key = $dep->getName() . '|' . $dep->getPackageManager()->value;
            if (!isset($scannedKeys[$key])) {
                $this->dependencyRepository->delete($dep);
            }
        }
    }
}

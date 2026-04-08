<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline\Stage;

use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Application\Pipeline\SyncStageInterface;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Override;

final readonly class UpdateDependencyStatusStage implements SyncStageInterface
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    #[Override]
    public function __invoke(SyncContext $context): SyncContext
    {
        $deps = $this->dependencyRepository->findByName(
            $context->packageName,
            $context->packageManager->value,
        );

        if ($deps === []) {
            return $context;
        }

        if ($context->latestVersion === null && $context->registryVersions === []) {
            foreach ($deps as $dep) {
                $dep->markRegistryStatus(RegistryStatus::NotFound);
                $this->dependencyRepository->save($dep);
            }

            return $context;
        }

        if ($context->latestVersion !== null) {
            foreach ($deps as $dep) {
                $dep->update(
                    latestVersion: $context->latestVersion,
                    isOutdated: \version_compare($dep->getCurrentVersion(), $context->latestVersion, '<'),
                );
                $dep->markRegistryStatus(RegistryStatus::Synced);
                $this->dependencyRepository->save($dep);
            }
        }

        return $context;
    }
}

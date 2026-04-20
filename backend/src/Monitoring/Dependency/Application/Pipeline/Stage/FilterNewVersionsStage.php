<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\Pipeline\Stage;

use App\Monitoring\Dependency\Application\Pipeline\SyncContext;
use App\Monitoring\Dependency\Application\Pipeline\SyncStageInterface;
use App\Monitoring\Dependency\Domain\Model\DependencyVersion;
use App\Monitoring\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use Override;

final readonly class FilterNewVersionsStage implements SyncStageInterface
{
    public function __construct(
        private DependencyVersionRepositoryInterface $versionRepository,
    ) {
    }

    #[Override]
    public function __invoke(SyncContext $context): SyncContext
    {
        if ($context->registryVersions === []) {
            return $context;
        }

        $existingVersions = $this->versionRepository->findByNameAndManager(
            $context->packageName,
            $context->packageManager,
        );
        $existingSet = [];
        foreach ($existingVersions as $v) {
            $existingSet[$v->getVersion()] = true;
        }

        $newVersions = [];
        $latestVersion = null;

        foreach ($context->registryVersions as $rv) {
            if ($rv->isLatest && $latestVersion === null) {
                $latestVersion = $rv->version;
            }

            if (isset($existingSet[$rv->version])) {
                continue;
            }

            $newVersions[] = DependencyVersion::create(
                dependencyName: $context->packageName,
                packageManager: $context->packageManager,
                version: $rv->version,
                releaseDate: $rv->releaseDate,
                isLatest: $rv->isLatest,
            );
        }

        $ctx = $context->withNewVersions($newVersions);

        if ($latestVersion !== null) {
            $ctx = $ctx->withLatestVersion($latestVersion);
        }

        return $ctx;
    }
}

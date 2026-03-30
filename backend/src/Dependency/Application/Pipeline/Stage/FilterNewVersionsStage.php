<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline\Stage;

use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Application\Pipeline\SyncStageInterface;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
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

        $newVersions = [];
        $latestVersion = null;

        foreach ($context->registryVersions as $rv) {
            if ($rv->isLatest && $latestVersion === null) {
                $latestVersion = $rv->version;
            }

            $existing = $this->versionRepository->findByNameManagerAndVersion(
                $context->packageName,
                $context->packageManager,
                $rv->version,
            );

            if ($existing !== null) {
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

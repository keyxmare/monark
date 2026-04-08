<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline\Stage;

use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Application\Pipeline\SyncStageInterface;
use App\Dependency\Domain\Port\PackageRegistryResolverPort;
use Override;

final readonly class FetchRegistryVersionsStage implements SyncStageInterface
{
    public function __construct(
        private PackageRegistryResolverPort $resolver,
    ) {
    }

    #[Override]
    public function __invoke(SyncContext $context): SyncContext
    {
        $versions = $this->resolver->fetchVersions(
            $context->packageName,
            $context->packageManager,
            $context->latestVersion,
        );

        return $context->withRegistryVersions($versions);
    }
}

<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline\Stage;

use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Application\Pipeline\SyncStageInterface;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use Override;

final readonly class PersistVersionsStage implements SyncStageInterface
{
    public function __construct(
        private DependencyVersionRepositoryInterface $versionRepository,
    ) {
    }

    #[Override]
    public function __invoke(SyncContext $context): SyncContext
    {
        if ($context->newVersions === []) {
            return $context;
        }

        $this->versionRepository->clearLatestFlag($context->packageName, $context->packageManager);

        foreach ($context->newVersions as $version) {
            $this->versionRepository->save($version);
        }

        $this->versionRepository->flush();

        return $context->withPersistedVersions($context->newVersions);
    }
}

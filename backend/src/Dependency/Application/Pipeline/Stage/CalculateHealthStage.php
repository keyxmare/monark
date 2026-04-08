<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline\Stage;

use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Application\Pipeline\SyncStageInterface;
use Override;

final readonly class CalculateHealthStage implements SyncStageInterface
{
    #[Override]
    public function __invoke(SyncContext $context): SyncContext
    {
        return $context;
    }
}

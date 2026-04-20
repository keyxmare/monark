<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\Pipeline\Stage;

use App\Monitoring\Dependency\Application\Pipeline\SyncContext;
use App\Monitoring\Dependency\Application\Pipeline\SyncStageInterface;
use Override;

final readonly class CalculateHealthStage implements SyncStageInterface
{
    #[Override]
    public function __invoke(SyncContext $context): SyncContext
    {
        return $context;
    }
}

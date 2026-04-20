<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\Pipeline;

interface SyncStageInterface
{
    public function __invoke(SyncContext $context): SyncContext;
}

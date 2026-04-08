<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline;

interface SyncStageInterface
{
    public function __invoke(SyncContext $context): SyncContext;
}

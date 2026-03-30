<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline;

final readonly class SyncPipeline
{
    /** @param list<SyncStageInterface> $stages */
    public function __construct(
        private array $stages,
    ) {
    }

    public function process(SyncContext $context): SyncContext
    {
        foreach ($this->stages as $stage) {
            $context = ($stage)($context);
        }

        return $context;
    }
}

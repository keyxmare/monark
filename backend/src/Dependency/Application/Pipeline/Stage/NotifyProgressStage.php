<?php

declare(strict_types=1);

namespace App\Dependency\Application\Pipeline\Stage;

use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Application\Pipeline\SyncStageInterface;
use Override;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final readonly class NotifyProgressStage implements SyncStageInterface
{
    public function __construct(
        private HubInterface $mercureHub,
    ) {
    }

    #[Override]
    public function __invoke(SyncContext $context): SyncContext
    {
        if ($context->syncId === null || $context->total === 0) {
            return $context;
        }

        $status = $context->index >= $context->total ? 'completed' : 'running';

        $this->mercureHub->publish(new Update(
            \sprintf('/dependency/sync/%s', $context->syncId),
            (string) \json_encode([
                'syncId' => $context->syncId,
                'completed' => $context->index,
                'total' => $context->total,
                'status' => $status,
                'lastPackage' => $context->packageName,
            ]),
        ));

        return $context;
    }
}

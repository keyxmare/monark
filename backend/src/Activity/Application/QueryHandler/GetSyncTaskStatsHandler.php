<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\SyncTaskStatsOutput;
use App\Activity\Application\Query\GetSyncTaskStatsQuery;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetSyncTaskStatsHandler
{
    public function __construct(
        private SyncTaskRepositoryInterface $syncTaskRepository,
    ) {
    }

    public function __invoke(GetSyncTaskStatsQuery $query): SyncTaskStatsOutput
    {
        return new SyncTaskStatsOutput(
            byType: $this->syncTaskRepository->countGroupedByType(),
            bySeverity: $this->syncTaskRepository->countGroupedBySeverity(),
            byStatus: $this->syncTaskRepository->countGroupedByStatus(),
        );
    }
}

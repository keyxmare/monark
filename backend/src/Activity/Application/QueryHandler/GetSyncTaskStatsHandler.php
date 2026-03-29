<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\SyncTaskStatsOutput;
use App\Activity\Application\Query\GetSyncTaskStatsQuery;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetSyncTaskStatsHandler
{
    public function __construct(
        private SyncTaskRepositoryInterface $syncTaskRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(GetSyncTaskStatsQuery $query): SyncTaskStatsOutput
    {
        return $this->cache->get('sync_task_stats', function (ItemInterface $item): SyncTaskStatsOutput {
            $item->expiresAfter(300);
            $item->tag(['sync_tasks']);

            return new SyncTaskStatsOutput(
                byType: $this->syncTaskRepository->countGroupedByType(),
                bySeverity: $this->syncTaskRepository->countGroupedBySeverity(),
                byStatus: $this->syncTaskRepository->countGroupedByStatus(),
            );
        });
    }
}

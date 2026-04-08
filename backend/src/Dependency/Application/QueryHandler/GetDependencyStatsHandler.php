<?php

declare(strict_types=1);

namespace App\Dependency\Application\QueryHandler;

use App\Dependency\Application\DTO\DependencyStatsOutput;
use App\Dependency\Application\Query\GetDependencyStatsQuery;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDependencyStatsHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(GetDependencyStatsQuery $query): DependencyStatsOutput
    {
        $filters = \array_filter([
            'projectId' => $query->projectId,
            'packageManager' => $query->packageManager,
            'type' => $query->type,
        ], static fn ($v) => $v !== null && $v !== '');

        $cacheKey = 'dependency_stats_' . \md5(\serialize($filters));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($filters): DependencyStatsOutput {
            $item->expiresAfter(300);
            $item->tag(['dependencies']);

            $stats = $this->dependencyRepository->getStatsSingle($filters);

            return new DependencyStatsOutput(
                total: $stats['total'],
                upToDate: $stats['total'] - $stats['outdated'],
                outdated: $stats['outdated'],
                totalVulnerabilities: $stats['totalVulnerabilities'],
            );
        });
    }
}

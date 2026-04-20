<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\EventListener;

use App\Monitoring\Catalog\Domain\Event\ProjectActivityCacheRefreshed;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class InvalidateProjectSummaryOnActivityRefresh
{
    public function __construct(
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(ProjectActivityCacheRefreshed $event): void
    {
        $this->cache->invalidateTags([
            'projects',
            'projects_summary',
            \sprintf('project_%s', $event->projectId),
        ]);
    }
}

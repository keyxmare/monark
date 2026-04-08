<?php

declare(strict_types=1);

namespace App\Dependency\Application\Policy;

use App\Shared\Domain\ValueObject\PackageManager;
use Psr\Cache\CacheItemPoolInterface;

final readonly class SyncThrottlePolicy
{
    private const int TTL_SECONDS = 3600;

    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function isAllowed(string $packageName, PackageManager $manager): bool
    {
        return !$this->cache->hasItem($this->cacheKey($packageName, $manager));
    }

    public function record(string $packageName, PackageManager $manager): void
    {
        $item = $this->cache->getItem($this->cacheKey($packageName, $manager));
        $item->set(true);
        $item->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($item);
    }

    private function cacheKey(string $packageName, PackageManager $manager): string
    {
        return \sprintf('sync_throttle_%s_%s', \str_replace('/', '_', $packageName), $manager->value);
    }
}

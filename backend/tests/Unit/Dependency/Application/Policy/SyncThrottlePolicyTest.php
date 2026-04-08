<?php

declare(strict_types=1);

use App\Dependency\Application\Policy\SyncThrottlePolicy;
use App\Shared\Domain\ValueObject\PackageManager;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

function makeThrottleCache(bool $isHit): CacheItemPoolInterface
{
    return new class ($isHit) implements CacheItemPoolInterface {
        public ?string $savedKey = null;
        public mixed $savedValue = null;

        public function __construct(private readonly bool $isHit)
        {
        }

        public function getItem(string $key): CacheItemInterface
        {
            return new class ($this->isHit, $key) implements CacheItemInterface {
                public function __construct(
                    private readonly bool $hit,
                    private readonly string $key,
                ) {
                }

                public function getKey(): string
                {
                    return $this->key;
                }

                public function get(): mixed
                {
                    return $this->hit ? true : null;
                }

                public function isHit(): bool
                {
                    return $this->hit;
                }

                public function set(mixed $value): static
                {
                    return $this;
                }

                public function expiresAt(?\DateTimeInterface $expiration): static
                {
                    return $this;
                }

                public function expiresAfter(\DateInterval|int|null $time): static
                {
                    return $this;
                }
            };
        }

        public function getItems(array $keys = []): iterable
        {
            return [];
        }

        public function hasItem(string $key): bool
        {
            return $this->isHit;
        }

        public function clear(): bool
        {
            return true;
        }

        public function deleteItem(string $key): bool
        {
            return true;
        }

        public function deleteItems(array $keys): bool
        {
            return true;
        }

        public function save(CacheItemInterface $item): bool
        {
            $this->savedKey = $item->getKey();

            return true;
        }

        public function saveDeferred(CacheItemInterface $item): bool
        {
            return true;
        }

        public function commit(): bool
        {
            return true;
        }
    };
}

describe('SyncThrottlePolicy', function () {
    it('allows sync when package has not been synced recently', function () {
        $cache = \makeThrottleCache(isHit: false);
        $policy = new SyncThrottlePolicy($cache);

        expect($policy->isAllowed('vue', PackageManager::Npm))->toBeTrue();
    });

    it('denies sync when package was synced within throttle window', function () {
        $cache = \makeThrottleCache(isHit: true);
        $policy = new SyncThrottlePolicy($cache);

        expect($policy->isAllowed('vue', PackageManager::Npm))->toBeFalse();
    });

    it('records sync after allowing', function () {
        $cache = \makeThrottleCache(isHit: false);
        $policy = new SyncThrottlePolicy($cache);

        $policy->record('vue', PackageManager::Npm);

        expect($cache->savedKey)->toContain('vue')
            ->and($cache->savedKey)->toContain('npm');
    });

    it('different packages have independent throttle state', function () {
        $cache = \makeThrottleCache(isHit: false);
        $policy = new SyncThrottlePolicy($cache);

        expect($policy->isAllowed('vue', PackageManager::Npm))->toBeTrue()
            ->and($policy->isAllowed('react', PackageManager::Npm))->toBeTrue();
    });
});

<?php

declare(strict_types=1);

use App\VersionRegistry\Domain\Port\VersionResolverInterface;
use App\VersionRegistry\Domain\Service\VersionResolverSelector;

function createResolver(string $source): VersionResolverInterface
{
    return new class ($source) implements VersionResolverInterface {
        public function __construct(private string $src)
        {
        }
        public function supports(string $resolverSource): bool
        {
            return $resolverSource === $this->src;
        }
        public function fetchVersions(string $productName, ?\DateTimeImmutable $since = null): array
        {
            return [];
        }
    };
}

describe('VersionResolverSelector', function () {
    it('returns the matching resolver', function () {
        $endoflife = \createResolver('endoflife');
        $registry = \createResolver('registry');

        $selector = new VersionResolverSelector([$endoflife, $registry]);
        $found = $selector->select('registry');

        expect($found)->toBe($registry);
    });

    it('returns null when no resolver matches', function () {
        $selector = new VersionResolverSelector([\createResolver('endoflife')]);

        expect($selector->select('unknown'))->toBeNull();
    });

    it('throws when constructed with empty resolver list', function () {
        new VersionResolverSelector([]);
    })->throws(\InvalidArgumentException::class);

    it('returns first matching resolver when multiple support same source', function () {
        $first = \createResolver('endoflife');
        $second = \createResolver('endoflife');

        $selector = new VersionResolverSelector([$first, $second]);
        $found = $selector->select('endoflife');

        expect($found)->toBe($first);
    });
});

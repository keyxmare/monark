<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use App\Coverage\Infrastructure\Provider\CoverageProviderRegistry;

describe('CoverageProviderRegistry', function (): void {
    it('resolves a provider that supports the type', function (): void {
        $provider = $this->createMock(CoverageProviderInterface::class);
        $provider->method('supports')->with(ProviderType::GitLab)->willReturn(true);

        $registry = new CoverageProviderRegistry([$provider]);

        expect($registry->resolve(ProviderType::GitLab))->toBe($provider);
    });

    it('returns null when no provider supports the type', function (): void {
        $provider = $this->createMock(CoverageProviderInterface::class);
        $provider->method('supports')->with(ProviderType::Bitbucket)->willReturn(false);

        $registry = new CoverageProviderRegistry([$provider]);

        expect($registry->resolve(ProviderType::Bitbucket))->toBeNull();
    });

    it('returns null with empty providers', function (): void {
        $registry = new CoverageProviderRegistry([]);

        expect($registry->resolve(ProviderType::GitLab))->toBeNull();
    });
});

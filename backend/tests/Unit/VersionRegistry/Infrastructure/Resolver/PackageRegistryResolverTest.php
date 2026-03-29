<?php

declare(strict_types=1);

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Port\PackageRegistryResolverPort;
use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Infrastructure\Resolver\PackageRegistryResolver;

describe('PackageRegistryResolver', function () {
    it('supports registry source', function () {
        $port = $this->createMock(PackageRegistryResolverPort::class);
        $resolver = new PackageRegistryResolver($port);
        expect($resolver->supports('registry'))->toBeTrue();
        expect($resolver->supports('endoflife'))->toBeFalse();
    });

    it('delegates to PackageRegistryResolverPort and converts results', function () {
        $port = $this->createMock(PackageRegistryResolverPort::class);
        $port->expects($this->once())
            ->method('fetchVersions')
            ->with('vue', PackageManager::Npm, null)
            ->willReturn([
                new RegistryVersion('3.5.13', new DateTimeImmutable('2025-01-15'), true),
                new RegistryVersion('3.5.12', new DateTimeImmutable('2025-01-01'), false),
            ]);

        $resolver = new PackageRegistryResolver($port);
        $versions = $resolver->fetchVersions('vue', null, PackageManager::Npm);

        expect($versions)->toHaveCount(2);
        expect($versions[0]->version)->toBe('3.5.13');
        expect($versions[0]->isLatest)->toBeTrue();
        expect($versions[0]->isLts)->toBeFalse();
        expect($versions[0]->eolDate)->toBeNull();
    });

    it('returns empty for null package manager', function () {
        $port = $this->createMock(PackageRegistryResolverPort::class);
        $resolver = new PackageRegistryResolver($port);
        expect($resolver->fetchVersions('php', null, null))->toBeEmpty();
    });
});

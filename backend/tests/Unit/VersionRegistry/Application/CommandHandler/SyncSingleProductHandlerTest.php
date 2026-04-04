<?php

declare(strict_types=1);

use App\VersionRegistry\Application\Command\SyncSingleProductCommand;
use App\VersionRegistry\Application\CommandHandler\SyncSingleProductHandler;
use App\VersionRegistry\Domain\DTO\ResolvedVersion;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Model\ProductType;
use App\VersionRegistry\Domain\Model\ResolverSource;
use App\VersionRegistry\Domain\Port\VersionResolverInterface;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use App\VersionRegistry\Domain\Service\VersionResolverSelector;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

describe('SyncSingleProductHandler', function () {
    it('syncs versions and dispatches event', function () {
        $product = Product::create('php', ProductType::Language, ResolverSource::EndOfLife);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->expects($this->once())
            ->method('findByNameAndManager')
            ->with('php', null)
            ->willReturn($product);
        $productRepo->expects($this->once())
            ->method('save')
            ->with($product);

        $versionRepo = $this->createMock(ProductVersionRepositoryInterface::class);
        $versionRepo->expects($this->once())
            ->method('clearLatestFlag')
            ->with('php', null);
        $versionRepo->expects($this->once())
            ->method('findByNameAndManager')
            ->with('php', null)
            ->willReturn([]);
        $versionRepo->expects($this->exactly(2))
            ->method('persist');
        $versionRepo->expects($this->once())
            ->method('flush');

        $resolver = $this->createMock(VersionResolverInterface::class);
        $resolver->expects($this->once())
            ->method('supports')
            ->with('endoflife')
            ->willReturn(true);
        $resolver->expects($this->once())
            ->method('fetchVersions')
            ->with('php', null)
            ->willReturn([
                new ResolvedVersion('8.4.2', new DateTimeImmutable('2025-02-13'), false, true, '2028-12-31'),
                new ResolvedVersion('8.3.16', new DateTimeImmutable('2025-01-16'), false, false, '2027-12-31'),
            ]);

        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $hub = $this->createMock(HubInterface::class);
        $hub->method('publish');

        $handler = new SyncSingleProductHandler(
            productRepository: $productRepo,
            versionRepository: $versionRepo,
            resolverSelector: new VersionResolverSelector([$resolver]),
            eventBus: $eventBus,
            mercureHub: $hub,
        );

        $handler(new SyncSingleProductCommand(
            productName: 'php',
            packageManager: null,
            resolverSource: 'endoflife',
            syncId: 'test-sync',
            index: 1,
            total: 1,
        ));

        expect($product->getLatestVersion())->toBe('8.4.2');
        expect($product->getLastSyncedAt())->not->toBeNull();
    });

    it('does nothing when product is not found', function () {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->expects($this->once())
            ->method('findByNameAndManager')
            ->willReturn(null);
        $productRepo->expects($this->never())->method('save');

        $versionRepo = $this->createMock(ProductVersionRepositoryInterface::class);
        $versionRepo->expects($this->never())->method('clearLatestFlag');

        $resolver = $this->createMock(VersionResolverInterface::class);
        $resolver->expects($this->never())->method('fetchVersions');

        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects($this->once())->method('dispatch')
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleProductHandler(
            productRepository: $productRepo,
            versionRepository: $versionRepo,
            resolverSelector: new VersionResolverSelector([$resolver]),
            eventBus: $eventBus,
            mercureHub: $hub,
        );

        $handler(new SyncSingleProductCommand(
            productName: 'unknown',
            packageManager: null,
            resolverSource: 'endoflife',
        ));
    });

    it('does nothing when no resolver supports the source', function () {
        $product = Product::create('php', ProductType::Language, ResolverSource::EndOfLife);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->expects($this->once())
            ->method('findByNameAndManager')
            ->willReturn($product);
        $productRepo->expects($this->never())->method('save');

        $versionRepo = $this->createMock(ProductVersionRepositoryInterface::class);
        $versionRepo->expects($this->never())->method('clearLatestFlag');

        $resolver = $this->createMock(VersionResolverInterface::class);
        $resolver->expects($this->once())
            ->method('supports')
            ->with('unknown-source')
            ->willReturn(false);
        $resolver->expects($this->never())->method('fetchVersions');

        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects($this->once())->method('dispatch')
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $hub = $this->createMock(HubInterface::class);

        $handler = new SyncSingleProductHandler(
            productRepository: $productRepo,
            versionRepository: $versionRepo,
            resolverSelector: new VersionResolverSelector([$resolver]),
            eventBus: $eventBus,
            mercureHub: $hub,
        );

        $handler(new SyncSingleProductCommand(
            productName: 'php',
            packageManager: null,
            resolverSource: 'unknown-source',
        ));
    });

    it('updates existing version instead of creating new one', function () {
        $product = Product::create('php', ProductType::Language, ResolverSource::EndOfLife);
        $existingVersion = App\VersionRegistry\Domain\Model\ProductVersion::create('php', '8.4.2');

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->expects($this->once())
            ->method('findByNameAndManager')
            ->willReturn($product);
        $productRepo->expects($this->once())->method('save');

        $versionRepo = $this->createMock(ProductVersionRepositoryInterface::class);
        $versionRepo->expects($this->once())->method('clearLatestFlag');
        $versionRepo->expects($this->once())
            ->method('findByNameAndManager')
            ->willReturn([$existingVersion]);
        $versionRepo->expects($this->never())->method('persist');
        $versionRepo->expects($this->once())->method('flush');

        $resolver = $this->createMock(VersionResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('fetchVersions')->willReturn([
            new ResolvedVersion('8.4.2', new DateTimeImmutable('2025-02-13'), false, true, null),
        ]);

        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->method('dispatch')->willReturnCallback(fn ($msg) => new Envelope($msg));

        $hub = $this->createMock(HubInterface::class);
        $hub->method('publish');

        $handler = new SyncSingleProductHandler(
            productRepository: $productRepo,
            versionRepository: $versionRepo,
            resolverSelector: new VersionResolverSelector([$resolver]),
            eventBus: $eventBus,
            mercureHub: $hub,
        );

        $handler(new SyncSingleProductCommand(
            productName: 'php',
            packageManager: null,
            resolverSource: 'endoflife',
            syncId: 'test-sync',
            index: 1,
            total: 2,
        ));

        expect($existingVersion->isLatest())->toBeTrue();
    });
});

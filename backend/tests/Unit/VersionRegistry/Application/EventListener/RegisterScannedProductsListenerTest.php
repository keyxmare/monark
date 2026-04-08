<?php

declare(strict_types=1);

use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Application\EventListener\RegisterScannedProductsListener;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Model\ProductType;
use App\VersionRegistry\Domain\Model\ResolverSource;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

describe('RegisterScannedProductsListener', function () {
    it('creates products for new languages and frameworks and dispatches sync', function () {
        $scanResult = new ScanResult(
            stacks: [
                new DetectedStack('PHP', 'Symfony', '8.4', '7.2.5'),
                new DetectedStack('TypeScript', 'Vue', '5.7', '3.5.13'),
            ],
            dependencies: [],
        );
        $event = new ProjectScannedEvent('project-1', $scanResult);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByNameAndManager')->willReturn(null);
        $productRepo->expects($this->exactly(4))->method('save');

        $commandBus = $this->createMock(MessageBusInterface::class);
        $commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(function ($msg) {
                return $msg instanceof SyncProductVersionsCommand && \count($msg->productNames) === 4;
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $listener = new RegisterScannedProductsListener($productRepo, $commandBus);
        $listener($event);
    });

    it('skips already known products', function () {
        $scanResult = new ScanResult(
            stacks: [new DetectedStack('PHP', 'Symfony', '8.4', '7.2.5')],
            dependencies: [],
        );
        $event = new ProjectScannedEvent('project-1', $scanResult);

        $existingProduct = Product::create('php', ProductType::Language, ResolverSource::EndOfLife);

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findByNameAndManager')->willReturnMap([
            ['php', null, $existingProduct],
            ['symfony', null, null],
        ]);
        $productRepo->expects($this->once())->method('save'); // only symfony

        $commandBus = $this->createMock(MessageBusInterface::class);
        $commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(fn ($msg) => $msg instanceof SyncProductVersionsCommand && \count($msg->productNames) === 1))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $listener = new RegisterScannedProductsListener($productRepo, $commandBus);
        $listener($event);
    });
});

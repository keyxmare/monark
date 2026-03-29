<?php

declare(strict_types=1);

namespace App\VersionRegistry\Application\CommandHandler;

use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Application\Command\SyncSingleProductCommand;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncProductVersionsHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(SyncProductVersionsCommand $command): int
    {
        $products = $command->productNames !== null
            ? $this->productRepository->findByNames($command->productNames)
            : $this->productRepository->findAll();

        $syncId = $command->syncId ?? Uuid::v7()->toRfc4122();
        $total = \count($products);

        foreach ($products as $index => $product) {
            $this->commandBus->dispatch(
                new SyncSingleProductCommand(
                    productName: $product->getName(),
                    packageManager: $product->getPackageManager()?->value,
                    resolverSource: $product->getResolverSource()->value,
                    syncId: $syncId,
                    index: $index + 1,
                    total: $total,
                ),
                [new DispatchAfterCurrentBusStamp()],
            );
        }

        return $total;
    }
}

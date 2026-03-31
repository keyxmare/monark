<?php

declare(strict_types=1);

namespace App\VersionRegistry\Application\CommandHandler;

use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Application\Command\SyncSingleProductCommand;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Port\PackageManagerAwareVersionResolverInterface;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use App\VersionRegistry\Domain\Service\VersionResolverSelector;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncSingleProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductVersionRepositoryInterface $versionRepository,
        private VersionResolverSelector $resolverSelector,
        private MessageBusInterface $eventBus,
        private HubInterface $mercureHub,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(SyncSingleProductCommand $command): void
    {
        $packageManager = $command->packageManager !== null
            ? PackageManager::tryFrom($command->packageManager)
            : null;

        $product = $this->productRepository->findByNameAndManager($command->productName, $packageManager);
        if ($product === null) {
            return;
        }

        $resolver = $this->resolverSelector->select($command->resolverSource);
        if ($resolver === null) {
            return;
        }

        $resolvedVersions = $resolver instanceof PackageManagerAwareVersionResolverInterface
            ? $resolver->fetchVersions($command->productName, $product->getLastSyncedAt(), $packageManager)
            : $resolver->fetchVersions($command->productName, $product->getLastSyncedAt());

        if ($resolvedVersions !== []) {
            $this->versionRepository->clearLatestFlag($command->productName, $packageManager);

            foreach ($resolvedVersions as $rv) {
                $existing = $this->versionRepository->findByNameManagerAndVersion(
                    $command->productName,
                    $packageManager,
                    $rv->version,
                );

                if ($existing !== null) {
                    $existing->markAsLatest($rv->isLatest);
                    $this->versionRepository->save($existing);
                    continue;
                }

                $version = ProductVersion::create(
                    productName: $command->productName,
                    version: $rv->version,
                    packageManager: $packageManager,
                    releaseDate: $rv->releaseDate,
                    isLts: $rv->isLts,
                    isLatest: $rv->isLatest,
                    eolDate: $rv->eolDate,
                );
                $this->versionRepository->save($version);
            }

            $latestVersion = null;
            $ltsVersion = null;
            $eolCycles = [];

            foreach ($resolvedVersions as $rv) {
                if ($rv->isLatest) {
                    $latestVersion = $rv->version;
                }
                if ($rv->isLts && ($ltsVersion === null)) {
                    $ltsVersion = $rv->version;
                }
                if ($rv->eolDate !== null) {
                    $eolCycles[] = [
                        'version' => $rv->version,
                        'eolDate' => $rv->eolDate,
                        'isLts' => $rv->isLts,
                    ];
                }
            }

            $product->updateSyncResult($latestVersion, $ltsVersion);
            $this->productRepository->save($product);

            $this->eventBus->dispatch(new ProductVersionsSyncedEvent(
                productName: $command->productName,
                packageManager: $packageManager,
                latestVersion: $latestVersion,
                ltsVersion: $ltsVersion,
                eolCycles: $eolCycles,
            ));

            $this->logger->info('Synced {count} versions for {product}', [
                'count' => \count($resolvedVersions),
                'product' => $command->productName,
            ]);
        }

        if ($command->syncId !== null && $command->total > 0) {
            $status = $command->index >= $command->total ? 'completed' : 'running';

            $this->mercureHub->publish(new Update(
                \sprintf('/version-registry/sync/%s', $command->syncId),
                (string) \json_encode([
                    'syncId' => $command->syncId,
                    'completed' => $command->index,
                    'total' => $command->total,
                    'status' => $status,
                    'lastProduct' => $command->productName,
                ]),
            ));
        }
    }

}

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
            $this->dispatchEvent($command->productName, $packageManager);

            return;
        }

        $resolver = $this->resolverSelector->select($command->resolverSource);
        if ($resolver === null) {
            $this->dispatchEvent($command->productName, $packageManager);

            return;
        }

        $resolvedVersions = $resolver instanceof PackageManagerAwareVersionResolverInterface
            ? $resolver->fetchVersions($command->productName, $product->getLastSyncedAt(), $packageManager)
            : $resolver->fetchVersions($command->productName, $product->getLastSyncedAt());

        $latestVersion = null;
        $ltsVersion = null;
        $eolCycles = [];

        if ($resolvedVersions !== []) {
            $this->versionRepository->clearLatestFlag($command->productName, $packageManager);

            $existingVersions = $this->versionRepository->findByNameAndManager($command->productName, $packageManager);
            $existingMap = [];
            foreach ($existingVersions as $ev) {
                $existingMap[$ev->getVersion()] = $ev;
            }

            foreach ($resolvedVersions as $rv) {
                $existing = $existingMap[$rv->version] ?? null;

                if ($existing !== null) {
                    $existing->markAsLatest($rv->isLatest);
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
                $this->versionRepository->persist($version);
            }

            $this->versionRepository->flush();

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

            $this->logger->info('Synced {count} versions for {product}', [
                'count' => \count($resolvedVersions),
                'product' => $command->productName,
            ]);
        }

        $this->dispatchEvent($command->productName, $packageManager, $latestVersion, $ltsVersion, $eolCycles);

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

    /** @param list<array{version: string, eolDate: string, isLts: bool}> $eolCycles */
    private function dispatchEvent(
        string $productName,
        ?PackageManager $packageManager,
        ?string $latestVersion = null,
        ?string $ltsVersion = null,
        array $eolCycles = [],
    ): void {
        $this->eventBus->dispatch(new ProductVersionsSyncedEvent(
            productName: $productName,
            packageManager: $packageManager,
            latestVersion: $latestVersion,
            ltsVersion: $ltsVersion,
            eolCycles: $eolCycles,
        ));
    }
}

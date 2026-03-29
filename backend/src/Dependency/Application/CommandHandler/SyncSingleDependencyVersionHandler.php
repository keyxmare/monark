<?php

declare(strict_types=1);

namespace App\Dependency\Application\CommandHandler;

use App\Dependency\Application\Command\SyncSingleDependencyVersionCommand;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Dependency\Domain\Port\PackageRegistryResolverPort;
use App\Shared\Domain\ValueObject\PackageManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncSingleDependencyVersionHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private DependencyVersionRepositoryInterface $versionRepository,
        private PackageRegistryResolverPort $registryFactory,
        private HubInterface $mercureHub,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(SyncSingleDependencyVersionCommand $command): void
    {
        $manager = PackageManager::tryFrom($command->packageManager);
        if ($manager === null) {
            return;
        }

        $latestKnown = $this->versionRepository->findLatestByNameAndManager($command->packageName, $manager);
        $sinceVersion = $latestKnown?->getVersion();

        $registryVersions = $this->registryFactory->fetchVersions($command->packageName, $manager, $sinceVersion);
        $deps = $this->dependencyRepository->findByName($command->packageName, $command->packageManager);

        if ($registryVersions === [] && $latestKnown === null) {
            foreach ($deps as $dep) {
                $dep->markRegistryStatus(RegistryStatus::NotFound);
                $this->dependencyRepository->save($dep);
            }
        } elseif ($registryVersions !== []) {
            $this->versionRepository->clearLatestFlag($command->packageName, $manager);

            foreach ($registryVersions as $rv) {
                $existing = $this->versionRepository->findByNameManagerAndVersion($command->packageName, $manager, $rv->version);
                if ($existing !== null) {
                    $existing->markAsLatest($rv->isLatest);
                    $this->versionRepository->save($existing);
                    continue;
                }

                $version = DependencyVersion::create(
                    dependencyName: $command->packageName,
                    packageManager: $manager,
                    version: $rv->version,
                    releaseDate: $rv->releaseDate,
                    isLatest: $rv->isLatest,
                );
                $this->versionRepository->save($version);
            }

            $latestVersion = null;
            foreach ($registryVersions as $rv) {
                if ($rv->isLatest) {
                    $latestVersion = $rv->version;
                    break;
                }
            }

            if ($latestVersion !== null) {
                foreach ($deps as $dep) {
                    $dep->update(
                        latestVersion: $latestVersion,
                        isOutdated: \version_compare($dep->getCurrentVersion(), $latestVersion, '<'),
                    );
                    $dep->markRegistryStatus(RegistryStatus::Synced);
                    $this->dependencyRepository->save($dep);
                }
            }

            $this->logger->info('Synced {count} versions for {package} ({manager})', [
                'count' => \count($registryVersions),
                'package' => $command->packageName,
                'manager' => $command->packageManager,
            ]);
        }

        if ($command->syncId !== null && $command->total > 0) {
            $status = $command->index >= $command->total ? 'completed' : 'running';
            $this->mercureHub->publish(new Update(
                \sprintf('/dependency/sync/%s', $command->syncId),
                (string) \json_encode([
                    'syncId' => $command->syncId,
                    'completed' => $command->index,
                    'total' => $command->total,
                    'status' => $status,
                    'lastPackage' => $command->packageName,
                ]),
            ));
        }
    }
}

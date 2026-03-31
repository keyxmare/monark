<?php

declare(strict_types=1);

namespace App\Dependency\Application\CommandHandler;

use App\Dependency\Application\Command\SyncSingleDependencyVersionCommand;
use App\Dependency\Application\Pipeline\Stage\CalculateHealthStage;
use App\Dependency\Domain\Event\DependencyVersionSynced;
use App\Dependency\Application\Pipeline\Stage\FetchRegistryVersionsStage;
use App\Dependency\Application\Pipeline\Stage\FilterNewVersionsStage;
use App\Dependency\Application\Pipeline\Stage\NotifyProgressStage;
use App\Dependency\Application\Pipeline\Stage\PersistVersionsStage;
use App\Dependency\Application\Pipeline\Stage\UpdateDependencyStatusStage;
use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Application\Pipeline\SyncPipeline;
use App\Dependency\Domain\Port\PackageRegistryResolverPort;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\ValueObject\PackageManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncSingleDependencyVersionHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private DependencyVersionRepositoryInterface $versionRepository,
        private PackageRegistryResolverPort $registryFactory,
        private HubInterface $mercureHub,
        private MessageBusInterface $eventBus,
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

        $ctx = SyncContext::initial($command->packageName, $manager);

        if ($latestKnown !== null) {
            $ctx = $ctx->withLatestVersion($latestKnown->getVersion());
        }

        if ($command->syncId !== null && $command->total > 0) {
            $ctx = $ctx->withProgress(
                syncId: $command->syncId,
                index: $command->index,
                total: $command->total,
            );
        }

        $pipeline = new SyncPipeline([
            new FetchRegistryVersionsStage($this->registryFactory),
            new FilterNewVersionsStage($this->versionRepository),
            new PersistVersionsStage($this->versionRepository),
            new UpdateDependencyStatusStage($this->dependencyRepository),
            new CalculateHealthStage(),
            new NotifyProgressStage($this->mercureHub),
        ]);

        $result = $pipeline->process($ctx);

        if ($result->registryVersions !== []) {
            $this->logger->info('Synced {count} versions for {package} ({manager})', [
                'count' => \count($result->registryVersions),
                'package' => $command->packageName,
                'manager' => $command->packageManager,
            ]);
        }

        $this->eventBus->dispatch(new DependencyVersionSynced(
            packageName: $command->packageName,
            packageManager: $command->packageManager,
        ));
    }
}

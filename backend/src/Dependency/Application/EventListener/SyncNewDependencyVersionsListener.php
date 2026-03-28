<?php

declare(strict_types=1);

namespace App\Dependency\Application\EventListener;

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\Event\ProjectScannedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class SyncNewDependencyVersionsListener
{
    public function __construct(
        private DependencyVersionRepositoryInterface $versionRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $newPackages = [];

        foreach ($event->scanResult->dependencies as $dep) {
            $manager = $dep->packageManager;
            $existing = $this->versionRepository->findLatestByNameAndManager($dep->name, $manager);

            if ($existing === null && !\in_array($dep->name, $newPackages, true)) {
                $newPackages[] = $dep->name;
            }
        }

        if ($newPackages === []) {
            return;
        }

        $this->commandBus->dispatch(new SyncDependencyVersionsCommand(
            packageNames: $newPackages,
        ));
    }
}

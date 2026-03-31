<?php

declare(strict_types=1);

namespace App\Dependency\Application\EventListener;

use App\Dependency\Application\Command\SyncDependencyCveCommand;
use App\Shared\Domain\Event\ProjectScannedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class SyncCveOnScanListener
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $this->commandBus->dispatch(new SyncDependencyCveCommand(
            projectId: $event->projectId,
        ));
    }
}

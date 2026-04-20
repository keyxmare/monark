<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\EventListener;

use App\Hub\Shared\Domain\Event\ProjectScannedEvent;
use App\Monitoring\Catalog\Application\Command\SyncLanguageCveCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class SyncLanguageCveOnScanListener
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $this->commandBus->dispatch(new SyncLanguageCveCommand(
            projectId: $event->projectId,
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\History\Application\EventListener;

use App\History\Application\Command\RecordProjectSnapshotCommand;
use App\History\Domain\Model\SnapshotSource;
use App\Shared\Domain\Event\ProjectScannedEvent;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class RecordSnapshotOnScanListener
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $now = new DateTimeImmutable();

        $this->commandBus->dispatch(new RecordProjectSnapshotCommand(
            projectId: $event->projectId,
            commitSha: \sprintf('live-%s', $now->format('YmdHis')),
            snapshotDate: $now,
            source: SnapshotSource::Live,
            scanResult: $event->scanResult,
        ));
    }
}

<?php

declare(strict_types=1);

use App\Hub\Shared\Domain\DTO\ScanResult;
use App\Hub\Shared\Domain\Event\ProjectScannedEvent;
use App\Monitoring\Catalog\Application\Command\SyncLanguageCveCommand;
use App\Monitoring\Catalog\Application\EventListener\SyncLanguageCveOnScanListener;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

describe('SyncLanguageCveOnScanListener', function () {
    it('dispatches SyncLanguageCveCommand on project scanned', function () {
        $bus = new class () implements MessageBusInterface {
            public array $dispatched = [];
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched[] = $message instanceof Envelope ? $message->getMessage() : $message;
                return Envelope::wrap($message, $stamps);
            }
        };

        $listener = new SyncLanguageCveOnScanListener($bus);
        $listener(new ProjectScannedEvent(
            projectId: 'proj-456',
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($bus->dispatched)->toHaveCount(1)
            ->and($bus->dispatched[0])->toBeInstanceOf(SyncLanguageCveCommand::class)
            ->and($bus->dispatched[0]->projectId)->toBe('proj-456');
    });
});

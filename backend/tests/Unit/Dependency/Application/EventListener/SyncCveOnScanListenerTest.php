<?php

declare(strict_types=1);

use App\Dependency\Application\Command\SyncDependencyCveCommand;
use App\Dependency\Application\EventListener\SyncCveOnScanListener;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

describe('SyncCveOnScanListener', function () {
    it('dispatches SyncDependencyCveCommand on project scanned', function () {
        $bus = new class () implements MessageBusInterface {
            public array $dispatched = [];
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched[] = $message instanceof Envelope ? $message->getMessage() : $message;
                return Envelope::wrap($message, $stamps);
            }
        };

        $listener = new SyncCveOnScanListener($bus);
        $listener(new ProjectScannedEvent(
            projectId: 'proj-123',
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($bus->dispatched)->toHaveCount(1);
        expect($bus->dispatched[0])->toBeInstanceOf(SyncDependencyCveCommand::class);
        expect($bus->dispatched[0]->projectId)->toBe('proj-123');
    });
});

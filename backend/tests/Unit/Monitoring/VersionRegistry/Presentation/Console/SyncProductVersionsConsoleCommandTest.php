<?php

declare(strict_types=1);

use App\Monitoring\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\Monitoring\VersionRegistry\Presentation\Console\SyncProductVersionsConsoleCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

function stubSyncProductVersionsConsoleBus(): MessageBusInterface
{
    return new class () implements MessageBusInterface {
        public ?object $dispatched = null;

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched = $message;

            return new Envelope($message);
        }
    };
}

describe('SyncProductVersionsConsoleCommand', function (): void {
    it('dispatches SyncProductVersionsCommand and returns success', function (): void {
        $bus = \stubSyncProductVersionsConsoleBus();
        $command = new SyncProductVersionsConsoleCommand($bus);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        expect($exitCode)->toBe(0);
        expect($tester->getDisplay())->toContain('Product version sync dispatched.');
        expect($bus->dispatched)->toBeInstanceOf(SyncProductVersionsCommand::class);
    });
});

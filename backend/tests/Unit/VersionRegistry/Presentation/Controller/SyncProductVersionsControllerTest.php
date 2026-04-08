<?php

declare(strict_types=1);

use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Presentation\Controller\SyncProductVersionsController;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

function stubSyncProductVersionsControllerBus(): MessageBusInterface
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

describe('SyncProductVersionsController', function (): void {
    it('returns 202 with generated syncId and dispatches command', function (): void {
        $bus = \stubSyncProductVersionsControllerBus();
        $controller = new SyncProductVersionsController($bus);

        $response = $controller();

        expect($response->getStatusCode())->toBe(202);

        $payload = \json_decode((string) $response->getContent(), true);
        expect($payload['syncId'])->toBeString();
        expect($payload['syncId'])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/');

        expect($bus->dispatched)->toBeInstanceOf(SyncProductVersionsCommand::class);
        /** @var SyncProductVersionsCommand $cmd */
        $cmd = $bus->dispatched;
        expect($cmd->syncId)->toBe($payload['syncId']);
    });
});

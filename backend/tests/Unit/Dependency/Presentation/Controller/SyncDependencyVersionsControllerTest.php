<?php

declare(strict_types=1);

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Presentation\Controller\SyncDependencyVersionsController;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

describe('SyncDependencyVersionsController', function () {
    it('dispatches sync command and returns 202', function () {
        $bus = new class extends stdClass implements MessageBusInterface {
            public ?object $dispatched = null;
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched = $message;
                return new Envelope($message);
            }
        };

        $response = (new SyncDependencyVersionsController($bus))();

        expect($response->getStatusCode())->toBe(202);
        expect($bus->dispatched)->toBeInstanceOf(SyncDependencyVersionsCommand::class);
        expect($bus->dispatched->syncId)->toBeString()->not->toBeEmpty();
    });
});

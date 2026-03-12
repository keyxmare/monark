<?php

declare(strict_types=1);

use App\Catalog\Application\Command\SyncMergeRequestsCommand;
use App\Catalog\Presentation\Console\SyncMergeRequestsConsoleCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

it('dispatches sync merge requests command', function () {
    $bus = $this->createMock(MessageBusInterface::class);
    $bus->expects($this->once())
        ->method('dispatch')
        ->with($this->isInstanceOf(SyncMergeRequestsCommand::class))
        ->willReturnCallback(fn (object $msg) => new Envelope($msg));

    $command = new SyncMergeRequestsConsoleCommand($bus);
    $tester = new CommandTester($command);
    $tester->execute(['projectId' => 'a0000000-0000-0000-0000-000000000001']);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('Syncing merge requests');
    expect($tester->getDisplay())->toContain('Done');
});

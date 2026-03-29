<?php

declare(strict_types=1);

use App\Activity\Domain\Port\MessageQueueMonitorInterface;
use App\Activity\Presentation\Command\PublishMessengerStatsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Mercure\HubInterface;

it('is registered with the correct name and description', function () {
    $monitor = new class implements MessageQueueMonitorInterface {
        public function getQueues(): array
        {
            return [];
        }

        public function getWorkers(): array
        {
            return [];
        }
    };

    $hub = $this->createMock(HubInterface::class);

    $command = new PublishMessengerStatsCommand($monitor, $hub);

    expect($command->getName())->toBe('app:messenger:publish-stats');
    expect($command->getDescription())->toBe('Continuously publish messenger stats to Mercure');
    expect($command)->toBeInstanceOf(Command::class);
});

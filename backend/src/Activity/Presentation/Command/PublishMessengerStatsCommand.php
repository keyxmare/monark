<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Command;

use App\Activity\Domain\Port\MessageQueueMonitorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsCommand(
    name: 'app:messenger:publish-stats',
    description: 'Continuously publish messenger stats to Mercure',
)]
final class PublishMessengerStatsCommand extends Command
{
    private const int INTERVAL_SECONDS = 5;

    public function __construct(
        private readonly MessageQueueMonitorInterface $monitor,
        private readonly HubInterface $mercureHub,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Publishing messenger stats to Mercure every ' . self::INTERVAL_SECONDS . 's...');

        $lastHash = '';

        while (true) {
            try {
                $queues = $this->monitor->getQueues();
                $workers = $this->monitor->getWorkers();

                $payload = \json_encode([
                    'queues' => $queues,
                    'workers' => $workers,
                ]);

                $hash = \md5($payload);
                if ($hash !== $lastHash) {
                    $this->mercureHub->publish(new Update('/messenger/stats', $payload));
                    $lastHash = $hash;
                    $output->writeln(\sprintf('[%s] Stats published (%d queues, %d workers)', \date('H:i:s'), \count($queues), \count($workers)), OutputInterface::VERBOSITY_VERBOSE);
                }
            } catch (\Throwable $e) {
                $output->writeln(\sprintf('<error>[%s] %s</error>', \date('H:i:s'), $e->getMessage()));
            }

            \sleep(self::INTERVAL_SECONDS);
        }
    }
}

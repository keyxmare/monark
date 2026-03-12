<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\MessengerStatsOutput;
use App\Activity\Application\Query\GetMessengerStatsQuery;
use App\Activity\Domain\Port\MessageQueueMonitorInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetMessengerStatsHandler
{
    public function __construct(
        private MessageQueueMonitorInterface $monitor,
    ) {
    }

    public function __invoke(GetMessengerStatsQuery $query): MessengerStatsOutput
    {
        return new MessengerStatsOutput(
            queues: $this->monitor->getQueues(),
            workers: $this->monitor->getWorkers(),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Activity\Domain\Port;

interface MessageQueueMonitorInterface
{
    /** @return list<array{name: string, messages: int, messages_ready: int, messages_unacknowledged: int, consumers: int, publish_rate: float, deliver_rate: float}> */
    public function getQueues(): array;

    /** @return list<array{connection: string, prefetch: int, state: string}> */
    public function getWorkers(): array;
}

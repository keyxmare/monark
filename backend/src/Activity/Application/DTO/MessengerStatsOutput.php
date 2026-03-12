<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class MessengerStatsOutput
{
    /**
     * @param list<array{name: string, messages: int, messages_ready: int, messages_unacknowledged: int, consumers: int, publish_rate: float, deliver_rate: float}> $queues
     * @param list<array{connection: string, prefetch: int, state: string}> $workers
     */
    public function __construct(
        public array $queues,
        public array $workers,
    ) {
    }

    /** @return array{queues: list<array{name: string, messages: int, messages_ready: int, messages_unacknowledged: int, consumers: int, publish_rate: float, deliver_rate: float}>, workers: list<array{connection: string, prefetch: int, state: string}>} */
    public function toArray(): array
    {
        return [
            'queues' => $this->queues,
            'workers' => $this->workers,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Adapter;

use App\Activity\Domain\Port\MessageQueueMonitorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class RabbitMqMonitor implements MessageQueueMonitorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $managementUrl,
    ) {
    }

    public function getQueues(): array
    {
        $response = $this->httpClient->request('GET', $this->managementUrl . '/api/queues/%2f');
        $data = $response->toArray();

        $queues = [];
        foreach ($data as $queue) {
            if (!\is_array($queue)) {
                continue;
            }

            /** @var array<string, mixed> $messageStats */
            $messageStats = \is_array($queue['message_stats'] ?? null) ? $queue['message_stats'] : [];
            /** @var array<string, mixed> $publishDetails */
            $publishDetails = \is_array($messageStats['publish_details'] ?? null) ? $messageStats['publish_details'] : [];
            /** @var array<string, mixed> $deliverDetails */
            $deliverDetails = \is_array($messageStats['deliver_get_details'] ?? null) ? $messageStats['deliver_get_details'] : [];

            $name = $queue['name'] ?? '';
            $messages = $queue['messages'] ?? 0;
            $messagesReady = $queue['messages_ready'] ?? 0;
            $messagesUnacked = $queue['messages_unacknowledged'] ?? 0;
            $consumers = $queue['consumers'] ?? 0;
            $publishRate = $publishDetails['rate'] ?? 0.0;
            $deliverRate = $deliverDetails['rate'] ?? 0.0;

            $queues[] = [
                'name' => \is_string($name) ? $name : '',
                'messages' => \is_int($messages) ? $messages : 0,
                'messages_ready' => \is_int($messagesReady) ? $messagesReady : 0,
                'messages_unacknowledged' => \is_int($messagesUnacked) ? $messagesUnacked : 0,
                'consumers' => \is_int($consumers) ? $consumers : 0,
                'publish_rate' => \is_float($publishRate) || \is_int($publishRate) ? (float) $publishRate : 0.0,
                'deliver_rate' => \is_float($deliverRate) || \is_int($deliverRate) ? (float) $deliverRate : 0.0,
            ];
        }

        return $queues;
    }

    public function getWorkers(): array
    {
        $response = $this->httpClient->request('GET', $this->managementUrl . '/api/channels');
        $channels = $response->toArray();

        $workers = [];
        foreach ($channels as $channel) {
            if (!\is_array($channel)) {
                continue;
            }

            $prefetchCount = $channel['prefetch_count'] ?? 0;
            $prefetch = \is_int($prefetchCount) ? $prefetchCount : 0;

            if ($prefetch > 0) {
                /** @var array<string, mixed> $connectionDetails */
                $connectionDetails = \is_array($channel['connection_details'] ?? null) ? $channel['connection_details'] : [];

                $peerHost = $connectionDetails['peer_host'] ?? 'unknown';
                $state = $channel['state'] ?? 'unknown';

                $workers[] = [
                    'connection' => \is_string($peerHost) ? $peerHost : 'unknown',
                    'prefetch' => $prefetch,
                    'state' => \is_string($state) ? $state : 'unknown',
                ];
            }
        }

        return $workers;
    }
}

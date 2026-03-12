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

        return \array_map(static function (array $queue): array {
            $messageStats = $queue['message_stats'] ?? [];

            return [
                'name' => $queue['name'],
                'messages' => $queue['messages'] ?? 0,
                'messages_ready' => $queue['messages_ready'] ?? 0,
                'messages_unacknowledged' => $queue['messages_unacknowledged'] ?? 0,
                'consumers' => $queue['consumers'] ?? 0,
                'publish_rate' => $messageStats['publish_details']['rate'] ?? 0.0,
                'deliver_rate' => $messageStats['deliver_get_details']['rate'] ?? 0.0,
            ];
        }, $data);
    }

    public function getWorkers(): array
    {
        $response = $this->httpClient->request('GET', $this->managementUrl . '/api/channels');
        $channels = $response->toArray();

        $workers = [];
        foreach ($channels as $channel) {
            if (($channel['prefetch_count'] ?? 0) > 0) {
                $workers[] = [
                    'connection' => $channel['connection_details']['peer_host'] ?? 'unknown',
                    'prefetch' => $channel['prefetch_count'],
                    'state' => $channel['state'] ?? 'unknown',
                ];
            }
        }

        return $workers;
    }
}

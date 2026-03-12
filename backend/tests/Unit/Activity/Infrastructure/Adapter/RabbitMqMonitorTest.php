<?php

declare(strict_types=1);

use App\Activity\Infrastructure\Adapter\RabbitMqMonitor;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

function stubRabbitMqResponse(array $data): ResponseInterface
{
    return new class ($data) implements ResponseInterface {
        public function __construct(private readonly array $data)
        {
        }

        public function getStatusCode(): int
        {
            return 200;
        }

        public function getHeaders(bool $throw = true): array
        {
            return [];
        }

        public function getContent(bool $throw = true): string
        {
            return (string) \json_encode($this->data);
        }

        public function toArray(bool $throw = true): array
        {
            return $this->data;
        }

        public function cancel(): void
        {
        }

        public function getInfo(?string $type = null): mixed
        {
            return null;
        }
    };
}

describe('RabbitMqMonitor', function () {
    it('returns queue stats from management API', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(stubRabbitMqResponse([
            [
                'name' => 'async',
                'messages' => 5,
                'messages_ready' => 3,
                'messages_unacknowledged' => 2,
                'consumers' => 1,
                'message_stats' => [
                    'publish_details' => ['rate' => 1.5],
                    'deliver_get_details' => ['rate' => 1.0],
                ],
            ],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $queues = $monitor->getQueues();

        expect($queues)->toHaveCount(1);
        expect($queues[0]['name'])->toBe('async');
        expect($queues[0]['messages'])->toBe(5);
        expect($queues[0]['publish_rate'])->toBe(1.5);
    });

    it('handles empty queue stats gracefully', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(stubRabbitMqResponse([
            ['name' => 'test', 'messages' => 0],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $queues = $monitor->getQueues();

        expect($queues)->toHaveCount(1);
        expect($queues[0]['publish_rate'])->toBe(0.0);
        expect($queues[0]['deliver_rate'])->toBe(0.0);
    });

    it('returns worker stats from channels API', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(stubRabbitMqResponse([
            [
                'prefetch_count' => 10,
                'connection_details' => ['peer_host' => '172.18.0.5'],
                'state' => 'running',
            ],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $workers = $monitor->getWorkers();

        expect($workers)->toHaveCount(1);
        expect($workers[0]['connection'])->toBe('172.18.0.5');
        expect($workers[0]['prefetch'])->toBe(10);
        expect($workers[0]['state'])->toBe('running');
    });

    it('skips channels with zero prefetch', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(stubRabbitMqResponse([
            ['prefetch_count' => 0, 'state' => 'idle'],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $workers = $monitor->getWorkers();

        expect($workers)->toBeEmpty();
    });
});

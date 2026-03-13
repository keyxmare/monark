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
    it('calls correct management API URL for queues', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://rabbitmq:15672/api/queues/%2f')
            ->willReturn(\stubRabbitMqResponse([]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $monitor->getQueues();
    });

    it('calls correct management API URL for channels', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://rabbitmq:15672/api/channels')
            ->willReturn(\stubRabbitMqResponse([]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $monitor->getWorkers();
    });

    it('handles non-int/non-string values in queue stats', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
            [
                'name' => 12345,
                'messages' => 'five',
                'messages_ready' => 1.5,
                'messages_unacknowledged' => false,
                'consumers' => null,
                'message_stats' => [
                    'publish_details' => ['rate' => 'fast'],
                    'deliver_get_details' => ['rate' => false],
                ],
            ],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $queues = $monitor->getQueues();

        expect($queues)->toHaveCount(1);
        expect($queues[0]['name'])->toBe('');
        expect($queues[0]['messages'])->toBe(0);
        expect($queues[0]['messages_ready'])->toBe(0);
        expect($queues[0]['messages_unacknowledged'])->toBe(0);
        expect($queues[0]['consumers'])->toBe(0);
        expect($queues[0]['publish_rate'])->toBe(0.0);
        expect($queues[0]['deliver_rate'])->toBe(0.0);
    });

    it('returns queue stats from management API', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
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
        expect($queues[0]['messages_ready'])->toBe(3);
        expect($queues[0]['messages_unacknowledged'])->toBe(2);
        expect($queues[0]['consumers'])->toBe(1);
        expect($queues[0]['publish_rate'])->toBe(1.5);
        expect($queues[0]['deliver_rate'])->toBe(1.0);
    });

    it('handles empty queue stats gracefully', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
            ['name' => 'test', 'messages' => 0],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $queues = $monitor->getQueues();

        expect($queues)->toHaveCount(1);
        expect($queues[0]['name'])->toBe('test');
        expect($queues[0]['messages'])->toBe(0);
        expect($queues[0]['messages_ready'])->toBe(0);
        expect($queues[0]['messages_unacknowledged'])->toBe(0);
        expect($queues[0]['consumers'])->toBe(0);
        expect($queues[0]['publish_rate'])->toBe(0.0);
        expect($queues[0]['deliver_rate'])->toBe(0.0);
    });

    it('skips non-array queue entries', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
            'not-an-array',
            ['name' => 'real', 'messages' => 1],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $queues = $monitor->getQueues();

        expect($queues)->toHaveCount(1);
        expect($queues[0]['name'])->toBe('real');
    });

    it('handles non-array channel entries in getWorkers', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
            'not-an-array',
            ['prefetch_count' => 5, 'connection_details' => ['peer_host' => '10.0.0.1'], 'state' => 'running'],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $workers = $monitor->getWorkers();

        expect($workers)->toHaveCount(1);
        expect($workers[0]['connection'])->toBe('10.0.0.1');
        expect($workers[0]['prefetch'])->toBe(5);
    });

    it('handles missing connection_details in worker', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
            ['prefetch_count' => 3, 'state' => 'idle'],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $workers = $monitor->getWorkers();

        expect($workers)->toHaveCount(1);
        expect($workers[0]['connection'])->toBe('unknown');
        expect($workers[0]['state'])->toBe('idle');
    });

    it('returns worker stats from channels API', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
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

    it('handles non-string values in worker data', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
            [
                'prefetch_count' => 5,
                'connection_details' => ['peer_host' => 12345],
                'state' => false,
            ],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $workers = $monitor->getWorkers();

        expect($workers)->toHaveCount(1);
        expect($workers[0]['connection'])->toBe('unknown');
        expect($workers[0]['state'])->toBe('unknown');
        expect($workers[0]['prefetch'])->toBe(5);
    });

    it('handles non-int prefetch_count', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
            ['prefetch_count' => 'ten', 'state' => 'running'],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $workers = $monitor->getWorkers();

        expect($workers)->toBeEmpty();
    });

    it('skips channels with zero prefetch', function () {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(\stubRabbitMqResponse([
            ['prefetch_count' => 0, 'state' => 'idle'],
        ]));

        $monitor = new RabbitMqMonitor($httpClient, 'http://rabbitmq:15672');
        $workers = $monitor->getWorkers();

        expect($workers)->toBeEmpty();
    });
});

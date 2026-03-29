<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Controller\HealthController;
use App\Shared\Infrastructure\Controller\ReadinessController;
use Doctrine\DBAL\Connection;

describe('HealthController', function () {
    it('returns healthy when database is reachable', function () {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('executeQuery')->with('SELECT 1');

        $response = (new HealthController($connection))();

        expect($response->getStatusCode())->toBe(200);
        $data = json_decode((string) $response->getContent(), true);
        expect($data['status'])->toBe('healthy');
        expect($data['checks']['database'])->toBe('ok');
    });

    it('returns unhealthy with 503 when database fails', function () {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $response = (new HealthController($connection))();

        expect($response->getStatusCode())->toBe(503);
        $data = json_decode((string) $response->getContent(), true);
        expect($data['status'])->toBe('unhealthy');
        expect($data['checks']['database'])->toBe('failed');
    });

    it('returns JSON content type', function () {
        $connection = $this->createMock(Connection::class);

        $response = (new HealthController($connection))();

        expect($response->headers->get('Content-Type'))->toContain('application/json');
    });

    it('returns checks key in response body', function () {
        $connection = $this->createMock(Connection::class);

        $response = (new HealthController($connection))();

        $data = json_decode((string) $response->getContent(), true);
        expect($data)->toHaveKey('checks');
        expect($data['checks'])->toHaveKey('database');
    });
});

describe('ReadinessController', function () {
    it('returns ready status with 200', function () {
        $response = (new ReadinessController())();

        expect($response->getStatusCode())->toBe(200);
        $data = json_decode((string) $response->getContent(), true);
        expect($data['status'])->toBe('ready');
    });

    it('returns JSON content type', function () {
        $response = (new ReadinessController())();

        expect($response->headers->get('Content-Type'))->toContain('application/json');
    });
});

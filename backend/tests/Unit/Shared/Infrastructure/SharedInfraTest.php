<?php

declare(strict_types=1);

use App\Shared\Application\DTO\ErrorOutput;
use App\Shared\Infrastructure\Controller\HealthController;
use App\Shared\Infrastructure\Controller\ReadinessController;
use App\Shared\Infrastructure\EventListener\SecurityHeadersListener;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

it('returns healthy when database is reachable', function () {
    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())->method('executeQuery')->with('SELECT 1');

    $response = (new HealthController($connection))();

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['status'])->toBe('healthy');
    expect($data['checks']['database'])->toBe('ok');
});

it('returns unhealthy when database fails', function () {
    $connection = $this->createMock(Connection::class);
    $connection->method('executeQuery')->willThrowException(new \RuntimeException('Connection refused'));

    $response = (new HealthController($connection))();

    expect($response->getStatusCode())->toBe(503);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['status'])->toBe('unhealthy');
    expect($data['checks']['database'])->toBe('failed');
});

it('returns ready status', function () {
    $response = (new ReadinessController())();

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['status'])->toBe('ready');
});

it('adds security headers on main request', function () {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/');
    $response = new Response();
    $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    (new SecurityHeadersListener())($event);

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
    expect($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    expect($response->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');
    expect($response->headers->has('Permissions-Policy'))->toBeTrue();
    expect($response->headers->has('Content-Security-Policy'))->toBeTrue();
});

it('skips security headers on sub-request', function () {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/');
    $response = new Response();
    $event = new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);

    (new SecurityHeadersListener())($event);

    expect($response->headers->has('X-Content-Type-Options'))->toBeFalse();
});

it('serializes ErrorOutput to array', function () {
    $error = new ErrorOutput('Not found', 404, ['field' => 'id']);

    expect($error->toArray())->toBe([
        'message' => 'Not found',
        'code' => 404,
        'errors' => ['field' => 'id'],
    ]);
});

it('serializes ErrorOutput with empty errors', function () {
    $error = new ErrorOutput('Server error', 500);

    expect($error->toArray())->toBe([
        'message' => 'Server error',
        'code' => 500,
        'errors' => [],
    ]);
});

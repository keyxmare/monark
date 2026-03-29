<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->client = static::createClient();
    $this->resetDatabase();
    $auth = $this->createAuthenticatedUser();
    $this->user = $auth['user'];
    $this->token = $auth['token'];
});

function createNotificationPayload(string $userEmail, array $overrides = []): array
{
    return array_merge([
        'title' => 'New vulnerability detected',
        'message' => 'CVE-2024-0001 found in symfony/http-kernel',
        'channel' => 'in_app',
        'userId' => $userEmail,
    ], $overrides);
}

function createNotificationViaApi(object $context, array $overrides = []): array
{
    $payload = createNotificationPayload($context->user->getUserIdentifier(), $overrides);

    $context->client->request('POST', '/api/v1/activity/notifications', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $context->token,
        'CONTENT_TYPE' => 'application/json',
    ], json_encode($payload));

    return json_decode($context->client->getResponse()->getContent(), true);
}

describe('POST /api/v1/activity/notifications', function () {
    it('creates a notification and returns 201', function () {
        $payload = createNotificationPayload($this->user->getUserIdentifier());

        $this->client->request('POST', '/api/v1/activity/notifications', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['title'])->toBe('New vulnerability detected');
        expect($body['data']['message'])->toBe('CVE-2024-0001 found in symfony/http-kernel');
        expect($body['data']['channel'])->toBe('in_app');
        expect($body['data']['readAt'])->toBeNull();
        expect($body['data']['userId'])->toBe($this->user->getUserIdentifier());
    });

    it('returns 422 for invalid payload', function () {
        $this->client->request('POST', '/api/v1/activity/notifications', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(['title' => '']));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(422);
    });
});

describe('GET /api/v1/activity/notifications', function () {
    it('lists notifications for current user with pagination', function () {
        createNotificationViaApi($this, ['title' => 'Notification 1']);
        createNotificationViaApi($this, ['title' => 'Notification 2']);

        $this->client->request('GET', '/api/v1/activity/notifications?page=1&per_page=10', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['items'])->toHaveCount(2);
    });
});

describe('PUT /api/v1/activity/notifications/{id}', function () {
    it('marks a notification as read', function () {
        $createBody = createNotificationViaApi($this);
        $notificationId = $createBody['data']['id'];

        $this->client->request('PUT', "/api/v1/activity/notifications/{$notificationId}", [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['readAt'])->not->toBeNull();
    });

    it('returns 404 for unknown notification', function () {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->client->request('PUT', "/api/v1/activity/notifications/{$fakeId}", [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(404);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

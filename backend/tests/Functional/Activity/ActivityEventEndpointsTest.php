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

function createEventPayload(string $userId, array $overrides = []): array
{
    return \array_merge([
        'type' => 'project.created',
        'entityType' => 'project',
        'entityId' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        'payload' => ['name' => 'My Project'],
        'userId' => $userId,
    ], $overrides);
}

function createEventViaApi(object $context, array $overrides = []): array
{
    $payload = \createEventPayload($context->user->getId()->toRfc4122(), $overrides);

    $context->client->request('POST', '/api/v1/activity/events', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $context->token,
        'CONTENT_TYPE' => 'application/json',
    ], \json_encode($payload));

    return \json_decode($context->client->getResponse()->getContent(), true);
}

describe('POST /api/v1/activity/events', function () {
    it('creates an activity event and returns 201', function () {
        $payload = \createEventPayload($this->user->getId()->toRfc4122());

        $this->client->request('POST', '/api/v1/activity/events', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['type'])->toBe('project.created');
        expect($body['data']['entityType'])->toBe('project');
        expect($body['data']['entityId'])->toBe('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee');
        expect($body['data']['payload'])->toBe(['name' => 'My Project']);
        expect($body['data']['userId'])->toBe($this->user->getId()->toRfc4122());
    });

    it('returns 422 for invalid payload', function () {
        $this->client->request('POST', '/api/v1/activity/events', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode(['type' => '']));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(422);
    });
});

describe('GET /api/v1/activity/events', function () {
    it('lists activity events with pagination', function () {
        \createEventViaApi($this, ['type' => 'project.created']);
        \createEventViaApi($this, ['type' => 'project.updated']);

        $this->client->request('GET', '/api/v1/activity/events?page=1&per_page=10', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['items'])->toHaveCount(2);
    });
});

describe('GET /api/v1/activity/events/{id}', function () {
    it('gets an activity event by id', function () {
        $createBody = \createEventViaApi($this);
        $eventId = $createBody['data']['id'];

        $this->client->request('GET', "/api/v1/activity/events/{$eventId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['id'])->toBe($eventId);
        expect($body['data']['type'])->toBe('project.created');
    });

    it('returns 404 for unknown event', function () {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->client->request('GET', "/api/v1/activity/events/{$fakeId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(404);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

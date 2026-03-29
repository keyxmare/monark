<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->client = static::createClient();
    $this->resetDatabase();
    $auth = $this->createAuthenticatedUser([
        'email' => 'admin@example.com',
        'firstName' => 'Admin',
        'lastName' => 'User',
    ]);
    $this->user = $auth['user'];
    $this->token = $auth['token'];
});

describe('GET /api/v1/identity/users', function () {
    it('lists users with pagination', function () {
        $this->client->request('GET', '/api/v1/identity/users', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['items'])->toBeArray();
        expect($body['data']['total'])->toBeGreaterThanOrEqual(1);
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
    });

    it('returns 401 without auth', function () {
        $this->client->request('GET', '/api/v1/identity/users');

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(401);
    });
});

describe('GET /api/v1/identity/users/{id}', function () {
    it('gets a user by id', function () {
        $userId = $this->user->getId()->toRfc4122();

        $this->client->request('GET', "/api/v1/identity/users/{$userId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['email'])->toBe('admin@example.com');
        expect($body['data']['id'])->toBe($userId);
    });

    it('returns 404 for unknown user', function () {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->client->request('GET', "/api/v1/identity/users/{$fakeId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(404);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

describe('PUT /api/v1/identity/users/{id}', function () {
    it('updates user fields', function () {
        $userId = $this->user->getId()->toRfc4122();

        $this->client->request('PUT', "/api/v1/identity/users/{$userId}", [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode([
            'firstName' => 'Updated',
            'lastName' => 'Name',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['firstName'])->toBe('Updated');
        expect($body['data']['lastName'])->toBe('Name');
    });
});

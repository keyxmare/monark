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

function createProviderPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'My GitLab',
        'type' => 'gitlab',
        'url' => 'https://gitlab.example.com',
        'apiToken' => 'glpat-token-123',
        'username' => 'admin',
    ], $overrides);
}

describe('POST /api/v1/catalog/providers', function () {
    it('creates a provider and returns 201', function () {
        $payload = createProviderPayload();

        $this->client->request('POST', '/api/v1/catalog/providers', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['name'])->toBe('My GitLab');
        expect($body['data']['type'])->toBe('gitlab');
    });
});

describe('GET /api/v1/catalog/providers', function () {
    it('lists providers with pagination', function () {
        $this->client->request('POST', '/api/v1/catalog/providers', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createProviderPayload()));

        $this->client->request('POST', '/api/v1/catalog/providers', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createProviderPayload([
            'name' => 'GitHub Provider',
            'type' => 'github',
            'url' => 'https://github.com',
        ])));

        $this->client->request('GET', '/api/v1/catalog/providers?page=1&per_page=10', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['items'])->toHaveCount(2);
    });
});

describe('GET /api/v1/catalog/providers/{id}', function () {
    it('gets a provider by id', function () {
        $this->client->request('POST', '/api/v1/catalog/providers', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createProviderPayload()));

        $createBody = json_decode($this->client->getResponse()->getContent(), true);
        $providerId = $createBody['data']['id'];

        $this->client->request('GET', "/api/v1/catalog/providers/{$providerId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['id'])->toBe($providerId);
        expect($body['data']['name'])->toBe('My GitLab');
    });

    it('returns 404 for unknown provider', function () {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->client->request('GET', "/api/v1/catalog/providers/{$fakeId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(404);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

describe('PUT /api/v1/catalog/providers/{id}', function () {
    it('updates a provider', function () {
        $this->client->request('POST', '/api/v1/catalog/providers', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createProviderPayload()));

        $createBody = json_decode($this->client->getResponse()->getContent(), true);
        $providerId = $createBody['data']['id'];

        $this->client->request('PUT', "/api/v1/catalog/providers/{$providerId}", [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode([
            'name' => 'Updated Provider',
            'url' => 'https://updated.example.com',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['name'])->toBe('Updated Provider');
    });
});

describe('DELETE /api/v1/catalog/providers/{id}', function () {
    it('deletes a provider', function () {
        $this->client->request('POST', '/api/v1/catalog/providers', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createProviderPayload()));

        $createBody = json_decode($this->client->getResponse()->getContent(), true);
        $providerId = $createBody['data']['id'];

        $this->client->request('DELETE', "/api/v1/catalog/providers/{$providerId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(204);

        // Verify it's gone
        $this->client->request('GET', "/api/v1/catalog/providers/{$providerId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(404);
    });
});

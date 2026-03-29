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

describe('POST /api/identity/access-tokens', function () {
    it('creates an access token and returns 201', function () {
        $this->client->request('POST', '/api/identity/access-tokens', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode([
            'provider' => 'gitlab',
            'token' => 'glpat-test-token-abc123',
            'scopes' => ['read_api', 'read_repository'],
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['provider'])->toBe('gitlab');
        expect($body['data']['scopes'])->toBe(['read_api', 'read_repository']);
        expect($body['data']['userId'])->toBe($this->user->getId()->toRfc4122());
        expect($body['data']['id'])->toBeString();
    });
});

describe('GET /api/identity/access-tokens', function () {
    it('lists access tokens for the current user', function () {
        // Create a token first
        $this->client->request('POST', '/api/identity/access-tokens', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode([
            'provider' => 'github',
            'token' => 'ghp_test123',
            'scopes' => ['repo'],
        ]));

        $this->client->request('GET', '/api/identity/access-tokens', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBeGreaterThanOrEqual(1);
    });
});

describe('DELETE /api/identity/access-tokens/{id}', function () {
    it('deletes an access token and returns 204', function () {
        // Create a token first
        $this->client->request('POST', '/api/identity/access-tokens', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode([
            'provider' => 'gitlab',
            'token' => 'glpat-delete-me',
            'scopes' => [],
        ]));

        $createBody = json_decode($this->client->getResponse()->getContent(), true);
        $tokenId = $createBody['data']['id'];

        $this->client->request('DELETE', "/api/identity/access-tokens/{$tokenId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(204);
    });
});

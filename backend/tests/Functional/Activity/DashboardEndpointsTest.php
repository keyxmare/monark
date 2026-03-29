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

describe('GET /api/v1/activity/dashboard', function () {
    it('returns dashboard data for authenticated user', function () {
        $this->client->request('GET', '/api/v1/activity/dashboard', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toBeArray();
        expect($body['data']['metrics'])->toBeArray();
        expect($body['data']['metrics'])->toHaveCount(3);
        expect($body['data']['metrics'][0])->toHaveKeys(['label', 'value']);
    });

    it('returns 401 without authentication', function () {
        $this->client->request('GET', '/api/v1/activity/dashboard');

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(401);
    });
});

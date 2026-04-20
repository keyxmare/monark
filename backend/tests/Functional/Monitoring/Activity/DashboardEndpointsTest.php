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

describe('GET /api/v1/monitoring/activity/dashboard', function () {
    it('returns dashboard data for authenticated user', function () {
        $this->client->request('GET', '/api/v1/monitoring/activity/dashboard', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toBeArray();
        expect($body['data'])->toHaveKeys([
            'projects',
            'commits_30d',
            'active_branches_30d',
            'dependencies_tracked',
            'vulnerabilities',
            'coverage_percent',
            'languages',
            'hosts',
        ]);
        expect($body['data']['projects'])->toBeInt();
        expect($body['data']['commits_30d'])->toBeInt();
        expect($body['data']['active_branches_30d'])->toBeInt();
        expect($body['data']['dependencies_tracked'])->toBeInt();
        expect($body['data']['vulnerabilities'])->toBeInt();
        expect($body['data']['languages'])->toBeArray();
        expect($body['data']['hosts'])->toBeArray();
    });

    it('returns 401 without authentication', function () {
        $this->client->request('GET', '/api/v1/monitoring/activity/dashboard');

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(401);
    });
});

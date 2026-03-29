<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->client = static::createClient();
    $this->resetDatabase();
});

describe('POST /api/v1/auth/register', function () {
    it('registers a new user and returns 201', function () {
        $this->client->request('POST', '/api/v1/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], \json_encode([
            'email' => 'new@example.com',
            'password' => 'securepass123',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['email'])->toBe('new@example.com');
        expect($body['data']['firstName'])->toBe('Jane');
        expect($body['data']['lastName'])->toBe('Doe');
        expect($body['data']['id'])->toBeString();
    });

    it('returns 422 for duplicate email', function () {
        $this->createAuthenticatedUser(['email' => 'dup@example.com']);

        $this->client->request('POST', '/api/v1/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], \json_encode([
            'email' => 'dup@example.com',
            'password' => 'securepass123',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(422);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });

    it('returns 422 for invalid email', function () {
        $this->client->request('POST', '/api/v1/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], \json_encode([
            'email' => 'not-an-email',
            'password' => 'securepass123',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(422);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });

    it('returns 422 for short password', function () {
        $this->client->request('POST', '/api/v1/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], \json_encode([
            'email' => 'valid@example.com',
            'password' => 'short',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(422);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

describe('POST /api/v1/auth/login', function () {
    it('returns 200 with token for valid credentials', function () {
        $this->createAuthenticatedUser([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $this->client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], \json_encode([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['token'])->toBeString();
        expect($body['data']['user']['email'])->toBe('login@example.com');
    });

    it('returns 401 for wrong password', function () {
        $this->createAuthenticatedUser([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $this->client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], \json_encode([
            'email' => 'login@example.com',
            'password' => 'wrongpassword',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(401);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

describe('POST /api/v1/auth/logout', function () {
    it('returns a successful response on logout', function () {
        $auth = $this->createAuthenticatedUser();

        $this->client->request('POST', '/api/v1/auth/logout', [], [], \array_merge(
            $this->authHeader($auth['token']),
            ['CONTENT_TYPE' => 'application/json'],
        ));

        $response = $this->client->getResponse();
        // Symfony's firewall logout handler intercepts the route and may redirect (302)
        // or return a JSON response depending on configuration.
        expect($response->getStatusCode())->toBeIn([200, 302]);
    });
});

describe('GET /api/v1/auth/profile', function () {
    it('returns current user when authenticated', function () {
        $auth = $this->createAuthenticatedUser([
            'email' => 'profile@example.com',
            'firstName' => 'Profile',
            'lastName' => 'User',
        ]);

        $this->client->request('GET', '/api/v1/auth/profile', [], [], $this->authHeader($auth['token']));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['email'])->toBe('profile@example.com');
        expect($body['data']['firstName'])->toBe('Profile');
    });

    it('returns 401 without token', function () {
        $this->client->request('GET', '/api/v1/auth/profile');

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(401);
    });
});

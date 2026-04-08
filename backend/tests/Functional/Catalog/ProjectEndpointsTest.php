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

function createProjectPayload(string $userId, array $overrides = []): array
{
    return \array_merge([
        'name' => 'My Project',
        'slug' => 'my-project',
        'description' => 'A test project',
        'repositoryUrl' => 'https://github.com/test/my-project',
        'defaultBranch' => 'main',
        'visibility' => 'private',
        'ownerId' => $userId,
    ], $overrides);
}

describe('POST /api/v1/catalog/projects', function () {
    it('creates a project and returns 201', function () {
        $payload = \createProjectPayload($this->user->getId()->toRfc4122());

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['name'])->toBe('My Project');
        expect($body['data']['slug'])->toBe('my-project');
    });

    it('returns 422 for duplicate slug', function () {
        $payload = \createProjectPayload($this->user->getId()->toRfc4122());

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));
        expect($this->client->getResponse()->getStatusCode())->toBe(201);

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(422);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });

    it('returns 422 for invalid slug format', function () {
        $payload = \createProjectPayload($this->user->getId()->toRfc4122(), [
            'slug' => 'Invalid Slug!',
        ]);

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(422);
    });
});

describe('GET /api/v1/catalog/projects', function () {
    it('lists projects with pagination', function () {
        $userId = $this->user->getId()->toRfc4122();

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode(\createProjectPayload($userId, ['slug' => 'project-a', 'name' => 'Project A'])));

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode(\createProjectPayload($userId, ['slug' => 'project-b', 'name' => 'Project B'])));

        $this->client->request('GET', '/api/v1/catalog/projects?page=1&per_page=10', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['items'])->toHaveCount(2);
    });
});

describe('GET /api/v1/catalog/projects/{id}', function () {
    it('gets a project by id', function () {
        $payload = \createProjectPayload($this->user->getId()->toRfc4122());

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));

        $createBody = \json_decode($this->client->getResponse()->getContent(), true);
        $projectId = $createBody['data']['id'];

        $this->client->request('GET', "/api/v1/catalog/projects/{$projectId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['id'])->toBe($projectId);
        expect($body['data']['name'])->toBe('My Project');
    });

    it('returns 404 for unknown project', function () {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->client->request('GET', "/api/v1/catalog/projects/{$fakeId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(404);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

describe('PUT /api/v1/catalog/projects/{id}', function () {
    it('updates a project', function () {
        $payload = \createProjectPayload($this->user->getId()->toRfc4122());

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));

        $createBody = \json_decode($this->client->getResponse()->getContent(), true);
        $projectId = $createBody['data']['id'];

        $this->client->request('PUT', "/api/v1/catalog/projects/{$projectId}", [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode([
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['name'])->toBe('Updated Name');
        expect($body['data']['description'])->toBe('Updated description');
    });
});

describe('DELETE /api/v1/catalog/projects/{id}', function () {
    it('deletes a project', function () {
        $payload = \createProjectPayload($this->user->getId()->toRfc4122());

        $this->client->request('POST', '/api/v1/catalog/projects', [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));

        $createBody = \json_decode($this->client->getResponse()->getContent(), true);
        $projectId = $createBody['data']['id'];

        $this->client->request('DELETE', "/api/v1/catalog/projects/{$projectId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(204);

        // Verify it's gone
        $this->client->request('GET', "/api/v1/catalog/projects/{$projectId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(404);
    });
});

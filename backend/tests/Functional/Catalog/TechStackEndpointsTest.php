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

    // Create a project to associate tech stacks with
    $this->client->request('POST', '/api/catalog/projects', [], [], array_merge(
        $this->authHeader($this->token),
        ['CONTENT_TYPE' => 'application/json'],
    ), json_encode([
        'name' => 'Tech Stack Project',
        'slug' => 'tech-stack-project',
        'description' => 'Project for tech stack tests',
        'repositoryUrl' => 'https://github.com/test/tech-stack-project',
        'defaultBranch' => 'main',
        'visibility' => 'private',
        'ownerId' => $this->user->getId()->toRfc4122(),
    ]));

    $createBody = json_decode($this->client->getResponse()->getContent(), true);
    $this->projectId = $createBody['data']['id'];
});

function createTechStackPayload(string $projectId, array $overrides = []): array
{
    return array_merge([
        'language' => 'PHP',
        'framework' => 'Symfony',
        'version' => '8.4',
        'detectedAt' => '2026-01-15T10:00:00+00:00',
        'projectId' => $projectId,
        'frameworkVersion' => '7.2',
    ], $overrides);
}

describe('POST /api/catalog/tech-stacks', function () {
    it('creates a tech stack and returns 201', function () {
        $payload = createTechStackPayload($this->projectId);

        $this->client->request('POST', '/api/catalog/tech-stacks', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['language'])->toBe('PHP');
        expect($body['data']['framework'])->toBe('Symfony');
    });
});

describe('GET /api/catalog/tech-stacks', function () {
    it('lists tech stacks with pagination', function () {
        $this->client->request('POST', '/api/catalog/tech-stacks', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createTechStackPayload($this->projectId)));

        $this->client->request('POST', '/api/catalog/tech-stacks', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createTechStackPayload($this->projectId, [
            'language' => 'TypeScript',
            'framework' => 'Vue',
            'version' => '5.0',
            'frameworkVersion' => '3.5',
        ])));

        $this->client->request('GET', "/api/catalog/tech-stacks?project_id={$this->projectId}&page=1&per_page=10", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['items'])->toHaveCount(2);
    });
});

describe('GET /api/catalog/tech-stacks/{id}', function () {
    it('gets a tech stack by id', function () {
        $this->client->request('POST', '/api/catalog/tech-stacks', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createTechStackPayload($this->projectId)));

        $createBody = json_decode($this->client->getResponse()->getContent(), true);
        $techStackId = $createBody['data']['id'];

        $this->client->request('GET', "/api/catalog/tech-stacks/{$techStackId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['id'])->toBe($techStackId);
        expect($body['data']['language'])->toBe('PHP');
    });
});

describe('DELETE /api/catalog/tech-stacks/{id}', function () {
    it('deletes a tech stack', function () {
        $this->client->request('POST', '/api/catalog/tech-stacks', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(createTechStackPayload($this->projectId)));

        $createBody = json_decode($this->client->getResponse()->getContent(), true);
        $techStackId = $createBody['data']['id'];

        $this->client->request('DELETE', "/api/catalog/tech-stacks/{$techStackId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(204);

        // Verify it's gone
        $this->client->request('GET', "/api/catalog/tech-stacks/{$techStackId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(404);
    });
});

<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->client = static::createClient();
    $this->resetDatabase();
    $auth = $this->createAuthenticatedUser();
    $this->user = $auth['user'];
    $this->token = $auth['token'];
    $this->projectId = Uuid::v7()->toRfc4122();
});

function createDependencyPayload(string $projectId, array $overrides = []): array
{
    return array_merge([
        'name' => 'symfony/framework-bundle',
        'currentVersion' => '7.0.0',
        'latestVersion' => '7.2.0',
        'ltsVersion' => '6.4.0',
        'packageManager' => 'composer',
        'type' => 'runtime',
        'isOutdated' => true,
        'projectId' => $projectId,
        'repositoryUrl' => 'https://github.com/symfony/framework-bundle',
    ], $overrides);
}

function createDependencyViaApi(object $context, array $overrides = []): array
{
    $payload = createDependencyPayload($context->projectId, $overrides);

    $context->client->request('POST', '/api/v1/dependency/dependencies', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $context->token,
        'CONTENT_TYPE' => 'application/json',
    ], json_encode($payload));

    return json_decode($context->client->getResponse()->getContent(), true);
}

describe('POST /api/v1/dependency/dependencies', function () {
    it('creates a dependency and returns 201', function () {
        $payload = createDependencyPayload($this->projectId);

        $this->client->request('POST', '/api/v1/dependency/dependencies', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['name'])->toBe('symfony/framework-bundle');
        expect($body['data']['currentVersion'])->toBe('7.0.0');
        expect($body['data']['latestVersion'])->toBe('7.2.0');
        expect($body['data']['ltsVersion'])->toBe('6.4.0');
        expect($body['data']['packageManager'])->toBe('composer');
        expect($body['data']['type'])->toBe('runtime');
        expect($body['data']['isOutdated'])->toBeTrue();
        expect($body['data']['projectId'])->toBe($this->projectId);
        expect($body['data']['repositoryUrl'])->toBe('https://github.com/symfony/framework-bundle');
        expect($body['data']['vulnerabilityCount'])->toBe(0);
        expect($body['data']['registryStatus'])->toBe('pending');
    });

    it('creates a dependency without repositoryUrl', function () {
        $payload = createDependencyPayload($this->projectId, [
            'repositoryUrl' => null,
        ]);
        unset($payload['repositoryUrl']);

        $this->client->request('POST', '/api/v1/dependency/dependencies', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = json_decode($response->getContent(), true);
        expect($body['data']['repositoryUrl'])->toBeNull();
    });

    it('returns 422 for invalid payload', function () {
        $this->client->request('POST', '/api/v1/dependency/dependencies', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(['name' => '']));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(422);
    });
});

describe('GET /api/v1/dependency/dependencies', function () {
    it('lists dependencies with pagination', function () {
        createDependencyViaApi($this, ['name' => 'package-a']);
        createDependencyViaApi($this, ['name' => 'package-b']);

        $this->client->request('GET', '/api/v1/dependency/dependencies?page=1&per_page=10', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['items'])->toHaveCount(2);
    });

    it('filters by package_manager', function () {
        createDependencyViaApi($this, ['name' => 'composer-pkg', 'packageManager' => 'composer']);
        createDependencyViaApi($this, ['name' => 'npm-pkg', 'packageManager' => 'npm']);

        $this->client->request('GET', '/api/v1/dependency/dependencies?package_manager=npm', [], [], $this->authHeader($this->token));

        $body = json_decode($this->client->getResponse()->getContent(), true);
        expect($body['data']['total'])->toBe(1);
        expect($body['data']['items'][0]['name'])->toBe('npm-pkg');
    });

    it('filters by is_outdated', function () {
        createDependencyViaApi($this, ['name' => 'outdated-pkg', 'isOutdated' => true]);
        createDependencyViaApi($this, ['name' => 'current-pkg', 'isOutdated' => false]);

        $this->client->request('GET', '/api/v1/dependency/dependencies?is_outdated=1', [], [], $this->authHeader($this->token));

        $body = json_decode($this->client->getResponse()->getContent(), true);
        expect($body['data']['total'])->toBe(1);
        expect($body['data']['items'][0]['name'])->toBe('outdated-pkg');
    });

    it('searches by name', function () {
        createDependencyViaApi($this, ['name' => 'symfony/console']);
        createDependencyViaApi($this, ['name' => 'laravel/framework']);

        $this->client->request('GET', '/api/v1/dependency/dependencies?search=symfony', [], [], $this->authHeader($this->token));

        $body = json_decode($this->client->getResponse()->getContent(), true);
        expect($body['data']['total'])->toBe(1);
        expect($body['data']['items'][0]['name'])->toBe('symfony/console');
    });
});

describe('GET /api/v1/dependency/dependencies/{id}', function () {
    it('gets a dependency by id', function () {
        $createBody = createDependencyViaApi($this);
        $dependencyId = $createBody['data']['id'];

        $this->client->request('GET', "/api/v1/dependency/dependencies/{$dependencyId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['id'])->toBe($dependencyId);
        expect($body['data']['name'])->toBe('symfony/framework-bundle');
    });

    it('returns 404 for unknown dependency', function () {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->client->request('GET', "/api/v1/dependency/dependencies/{$fakeId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(404);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

describe('PUT /api/v1/dependency/dependencies/{id}', function () {
    it('updates a dependency', function () {
        $createBody = createDependencyViaApi($this);
        $dependencyId = $createBody['data']['id'];

        $this->client->request('PUT', "/api/v1/dependency/dependencies/{$dependencyId}", [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode([
            'name' => 'symfony/console',
            'currentVersion' => '7.2.0',
            'isOutdated' => false,
        ]));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['name'])->toBe('symfony/console');
        expect($body['data']['currentVersion'])->toBe('7.2.0');
        expect($body['data']['isOutdated'])->toBeFalse();
    });
});

describe('DELETE /api/v1/dependency/dependencies/{id}', function () {
    it('deletes a dependency', function () {
        $createBody = createDependencyViaApi($this);
        $dependencyId = $createBody['data']['id'];

        $this->client->request('DELETE', "/api/v1/dependency/dependencies/{$dependencyId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(204);

        // Verify it's gone
        $this->client->request('GET', "/api/v1/dependency/dependencies/{$dependencyId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(404);
    });
});

describe('GET /api/v1/dependency/stats', function () {
    it('returns dependency stats', function () {
        createDependencyViaApi($this, ['name' => 'outdated-pkg', 'isOutdated' => true]);
        createDependencyViaApi($this, ['name' => 'current-pkg', 'isOutdated' => false]);

        $this->client->request('GET', '/api/v1/dependency/stats', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['upToDate'])->toBe(1);
        expect($body['data']['outdated'])->toBe(1);
        expect($body['data']['totalVulnerabilities'])->toBe(0);
    });

    it('returns empty stats when no dependencies exist', function () {
        $this->client->request('GET', '/api/v1/dependency/stats', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['data']['total'])->toBe(0);
        expect($body['data']['outdated'])->toBe(0);
    });
});

describe('POST /api/v1/dependency/sync', function () {
    it('triggers sync and returns 202 with syncId', function () {
        $this->client->request('POST', '/api/v1/dependency/sync', [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(202);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKey('syncId');
        expect($body['data']['syncId'])->toBeString();
        expect(Uuid::isValid($body['data']['syncId']))->toBeTrue();
    });
});

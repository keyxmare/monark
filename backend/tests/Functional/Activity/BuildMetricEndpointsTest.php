<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;
use Doctrine\ORM\EntityManagerInterface;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->client = static::createClient();
    $this->resetDatabase();
    $auth = $this->createAuthenticatedUser();
    $this->user = $auth['user'];
    $this->token = $auth['token'];

    // Create a project for build metrics
    $em = self::getContainer()->get(EntityManagerInterface::class);
    $project = Project::create(
        name: 'Test Project',
        slug: 'test-project',
        description: 'A test project for build metrics',
        repositoryUrl: 'https://github.com/test/test-project',
        defaultBranch: 'main',
        visibility: ProjectVisibility::Private,
        ownerId: $this->user->getId(),
    );
    $em->persist($project);
    $em->flush();
    $em->clear();

    $this->projectId = $project->getId()->toRfc4122();
});

function createBuildMetricPayload(array $overrides = []): array
{
    return \array_merge([
        'commitSha' => 'abc123def456789012345678901234abcdef1234',
        'ref' => 'refs/heads/main',
        'backendCoverage' => 82.5,
        'frontendCoverage' => 75.3,
        'mutationScore' => 80.1,
    ], $overrides);
}

function createBuildMetricViaApi(object $context, array $overrides = []): array
{
    $payload = \createBuildMetricPayload($overrides);

    $context->client->request('POST', "/api/v1/activity/projects/{$context->projectId}/build-metrics", [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $context->token,
        'CONTENT_TYPE' => 'application/json',
    ], \json_encode($payload));

    return \json_decode($context->client->getResponse()->getContent(), true);
}

describe('POST /api/v1/activity/projects/{projectId}/build-metrics', function () {
    it('creates a build metric and returns 201', function () {
        $payload = \createBuildMetricPayload();

        $this->client->request('POST', "/api/v1/activity/projects/{$this->projectId}/build-metrics", [], [], \array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), \json_encode($payload));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(201);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['commitSha'])->toBe('abc123def456789012345678901234abcdef1234');
        expect($body['data']['ref'])->toBe('refs/heads/main');
        expect($body['data']['backendCoverage'])->toBe(82.5);
        expect($body['data']['frontendCoverage'])->toBe(75.3);
        expect($body['data']['mutationScore'])->toBe(80.1);
        expect($body['data']['projectId'])->toBe($this->projectId);
    });
});

describe('GET /api/v1/activity/projects/{projectId}/build-metrics', function () {
    it('lists build metrics with pagination', function () {
        \createBuildMetricViaApi($this, ['commitSha' => 'aaa1111111111111111111111111111111111111']);
        \createBuildMetricViaApi($this, ['commitSha' => 'bbb2222222222222222222222222222222222222']);

        $this->client->request('GET', "/api/v1/activity/projects/{$this->projectId}/build-metrics?page=1&per_page=10", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['items'])->toHaveCount(2);
    });
});

describe('GET /api/v1/activity/projects/{projectId}/build-metrics/latest', function () {
    it('gets the latest build metric', function () {
        \createBuildMetricViaApi($this, ['commitSha' => 'aaa1111111111111111111111111111111111111']);
        \createBuildMetricViaApi($this, ['commitSha' => 'bbb2222222222222222222222222222222222222']);

        $this->client->request('GET', "/api/v1/activity/projects/{$this->projectId}/build-metrics/latest", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['projectId'])->toBe($this->projectId);
    });
});

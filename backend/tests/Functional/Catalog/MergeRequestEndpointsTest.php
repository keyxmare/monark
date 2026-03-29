<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Model\Project;
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

    // Create a project via API
    $this->client->request('POST', '/api/catalog/projects', [], [], array_merge(
        $this->authHeader($this->token),
        ['CONTENT_TYPE' => 'application/json'],
    ), json_encode([
        'name' => 'MR Project',
        'slug' => 'mr-project',
        'description' => 'Project for merge request tests',
        'repositoryUrl' => 'https://github.com/test/mr-project',
        'defaultBranch' => 'main',
        'visibility' => 'private',
        'ownerId' => $this->user->getId()->toRfc4122(),
    ]));

    $createBody = json_decode($this->client->getResponse()->getContent(), true);
    $this->projectId = $createBody['data']['id'];

    // Insert a merge request directly via the entity manager
    $em = self::getContainer()->get(EntityManagerInterface::class);
    $project = $em->getRepository(Project::class)->find($this->projectId);

    $mr = MergeRequest::create(
        externalId: '101',
        title: 'Fix login bug',
        description: 'Fixes the login issue on mobile',
        sourceBranch: 'fix/login-bug',
        targetBranch: 'main',
        status: MergeRequestStatus::Open,
        author: 'dev@example.com',
        url: 'https://github.com/test/mr-project/pull/101',
        additions: 42,
        deletions: 10,
        reviewers: ['reviewer@example.com'],
        labels: ['bug', 'priority:high'],
        mergedAt: null,
        closedAt: null,
        project: $project,
    );

    $em->persist($mr);
    $em->flush();

    $this->mergeRequestId = $mr->getId()->toRfc4122();
});

describe('GET /api/catalog/projects/{projectId}/merge-requests', function () {
    it('lists merge requests for a project', function () {
        $this->client->request(
            'GET',
            "/api/catalog/projects/{$this->projectId}/merge-requests?page=1&per_page=10",
            [],
            [],
            $this->authHeader($this->token),
        );

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(1);
        expect($body['data']['items'])->toHaveCount(1);
        expect($body['data']['items'][0]['title'])->toBe('Fix login bug');
    });
});

describe('GET /api/catalog/merge-requests/{id}', function () {
    it('gets a merge request by id', function () {
        $this->client->request(
            'GET',
            "/api/catalog/merge-requests/{$this->mergeRequestId}",
            [],
            [],
            $this->authHeader($this->token),
        );

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['id'])->toBe($this->mergeRequestId);
        expect($body['data']['title'])->toBe('Fix login bug');
        expect($body['data']['status'])->toBe('open');
        expect($body['data']['author'])->toBe('dev@example.com');
    });
});

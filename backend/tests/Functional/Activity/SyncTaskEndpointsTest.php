<?php

declare(strict_types=1);

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->client = static::createClient();
    $this->resetDatabase();
    $auth = $this->createAuthenticatedUser();
    $this->user = $auth['user'];
    $this->token = $auth['token'];
    $this->projectId = Uuid::v7();
    $this->em = self::getContainer()->get(EntityManagerInterface::class);
});

function seedSyncTask(object $context, array $overrides = []): SyncTask
{
    $em = $context->em;

    $task = SyncTask::create(
        type: $overrides['type'] ?? SyncTaskType::OutdatedDependency,
        severity: $overrides['severity'] ?? SyncTaskSeverity::High,
        title: $overrides['title'] ?? 'Outdated symfony/http-kernel',
        description: $overrides['description'] ?? 'Version 7.0.0 is outdated, latest is 7.2.0',
        metadata: $overrides['metadata'] ?? ['dependencyName' => 'symfony/http-kernel'],
        projectId: $overrides['projectId'] ?? $context->projectId,
    );

    $em->persist($task);
    $em->flush();
    $em->clear();

    return $task;
}

describe('GET /api/activity/sync-tasks', function () {
    it('lists sync tasks with pagination', function () {
        seedSyncTask($this, ['title' => 'Task A']);
        seedSyncTask($this, ['title' => 'Task B']);

        $this->client->request('GET', '/api/activity/sync-tasks?page=1&per_page=10', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['items', 'total', 'page', 'per_page', 'total_pages']);
        expect($body['data']['total'])->toBe(2);
        expect($body['data']['items'])->toHaveCount(2);
    });

    it('filters sync tasks by status', function () {
        $task = seedSyncTask($this, ['title' => 'Open Task']);
        seedSyncTask($this, ['title' => 'Another Task']);

        // Resolve the first task via PATCH
        $this->client->request('PATCH', '/api/activity/sync-tasks/' . $task->getId()->toRfc4122(), [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(['status' => 'resolved']));

        $this->client->request('GET', '/api/activity/sync-tasks?status=open', [], [], $this->authHeader($this->token));

        $body = json_decode($this->client->getResponse()->getContent(), true);
        expect($body['data']['total'])->toBe(1);
    });
});

describe('GET /api/activity/sync-tasks/{id}', function () {
    it('gets a sync task by id', function () {
        $task = seedSyncTask($this);

        $this->client->request('GET', '/api/activity/sync-tasks/' . $task->getId()->toRfc4122(), [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['id'])->toBe($task->getId()->toRfc4122());
        expect($body['data']['title'])->toBe('Outdated symfony/http-kernel');
        expect($body['data']['status'])->toBe('open');
        expect($body['data']['severity'])->toBe('high');
        expect($body['data']['type'])->toBe('outdated_dependency');
    });

    it('returns 404 for unknown sync task', function () {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->client->request('GET', "/api/activity/sync-tasks/{$fakeId}", [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(404);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeFalse();
    });
});

describe('PATCH /api/activity/sync-tasks/{id}', function () {
    it('updates sync task status', function () {
        $task = seedSyncTask($this);

        $this->client->request('PATCH', '/api/activity/sync-tasks/' . $task->getId()->toRfc4122(), [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(['status' => 'acknowledged']));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data']['status'])->toBe('acknowledged');
    });

    it('sets resolvedAt when status is resolved', function () {
        $task = seedSyncTask($this);

        $this->client->request('PATCH', '/api/activity/sync-tasks/' . $task->getId()->toRfc4122(), [], [], array_merge(
            $this->authHeader($this->token),
            ['CONTENT_TYPE' => 'application/json'],
        ), json_encode(['status' => 'resolved']));

        $body = json_decode($this->client->getResponse()->getContent(), true);
        expect($body['data']['status'])->toBe('resolved');
        expect($body['data']['resolvedAt'])->not->toBeNull();
    });
});

describe('GET /api/activity/sync-tasks/stats', function () {
    it('returns sync task stats', function () {
        seedSyncTask($this, ['type' => SyncTaskType::OutdatedDependency, 'severity' => SyncTaskSeverity::High]);
        seedSyncTask($this, ['type' => SyncTaskType::Vulnerability, 'severity' => SyncTaskSeverity::Critical]);

        $this->client->request('GET', '/api/activity/sync-tasks/stats', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toHaveKeys(['by_type', 'by_severity', 'by_status']);
    });
});

<?php

declare(strict_types=1);

use App\Activity\Application\DTO\MessengerStatsOutput;
use App\Activity\Application\DTO\SyncTaskStatsOutput;
use App\Activity\Application\Query\GetMessengerStatsQuery;
use App\Activity\Application\Query\GetSyncTaskStatsQuery;
use App\Activity\Application\QueryHandler\GetMessengerStatsHandler;
use App\Activity\Application\QueryHandler\GetSyncTaskStatsHandler;
use App\Activity\Domain\Port\MessageQueueMonitorInterface;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;

describe('GetMessengerStatsHandler', function () {
    it('returns messenger stats from monitor', function () {
        $monitor = $this->createMock(MessageQueueMonitorInterface::class);
        $monitor->method('getQueues')->willReturn([['name' => 'async', 'messages' => 5, 'messages_ready' => 3, 'messages_unacknowledged' => 2, 'consumers' => 1, 'publish_rate' => 1.5, 'deliver_rate' => 1.0]]);
        $monitor->method('getWorkers')->willReturn([['connection' => 'default', 'prefetch' => 10, 'state' => 'running']]);

        $handler = new GetMessengerStatsHandler($monitor);
        $result = $handler(new GetMessengerStatsQuery());

        expect($result)->toBeInstanceOf(MessengerStatsOutput::class);
        expect($result->queues)->toHaveCount(1);
        expect($result->workers)->toHaveCount(1);
    });
});

describe('GetSyncTaskStatsHandler', function () {
    it('returns sync task stats', function () {
        $repo = $this->createMock(SyncTaskRepositoryInterface::class);
        $repo->method('countGroupedByType')->willReturn([['label' => 'vulnerability', 'count' => 3]]);
        $repo->method('countGroupedBySeverity')->willReturn([['label' => 'critical', 'count' => 1]]);
        $repo->method('countGroupedByStatus')->willReturn([['label' => 'open', 'count' => 2]]);

        $handler = new GetSyncTaskStatsHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());
        $result = $handler(new GetSyncTaskStatsQuery());

        expect($result)->toBeInstanceOf(SyncTaskStatsOutput::class);
        expect($result->byType)->toHaveCount(1);
        expect($result->bySeverity)->toHaveCount(1);
        expect($result->byStatus)->toHaveCount(1);
    });
});

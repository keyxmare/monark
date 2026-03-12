<?php

declare(strict_types=1);

use App\Catalog\Application\EventListener\IncrementSyncJobProgressListener;
use App\Catalog\Domain\Event\ProjectSyncCompletedEvent;
use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

describe('IncrementSyncJobProgressListener', function () {
    it('increments completed projects and publishes mercure update', function () {
        $syncJob = SyncJob::create(3);
        $syncJobId = $syncJob->getId()->toRfc4122();

        $repo = $this->createMock(SyncJobRepositoryInterface::class);
        $repo->method('findById')->willReturn($syncJob);
        $repo->expects($this->once())->method('save')->with($syncJob);

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('publish')->with($this->isInstanceOf(Update::class));

        $listener = new IncrementSyncJobProgressListener($repo, $hub);
        $listener(new ProjectSyncCompletedEvent('p-1', $syncJobId));

        expect($syncJob->getCompletedProjects())->toBe(1);
    });

    it('silently returns when sync job not found', function () {
        $repo = $this->createMock(SyncJobRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $repo->expects($this->never())->method('save');

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('publish');

        $listener = new IncrementSyncJobProgressListener($repo, $hub);
        $listener(new ProjectSyncCompletedEvent('p-1', 'a0000000-0000-0000-0000-000000000001'));
    });

    it('marks job completed when all projects done', function () {
        $syncJob = SyncJob::create(1);
        $syncJobId = $syncJob->getId()->toRfc4122();

        $repo = $this->createMock(SyncJobRepositoryInterface::class);
        $repo->method('findById')->willReturn($syncJob);

        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())->method('publish');

        $listener = new IncrementSyncJobProgressListener($repo, $hub);
        $listener(new ProjectSyncCompletedEvent('p-1', $syncJobId));

        expect($syncJob->getStatus()->value)->toBe('completed');
        expect($syncJob->getCompletedAt())->not->toBeNull();
    });
});

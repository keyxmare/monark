<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Model\SyncJobStatus;
use Symfony\Component\Uid\Uuid;

describe('SyncJob', function () {
    it('creates a sync job with all fields', function () {
        $providerId = Uuid::v7();
        $job = SyncJob::create(totalProjects: 10, providerId: $providerId);

        expect($job->getId())->not->toBeNull();
        expect($job->getTotalProjects())->toBe(10);
        expect($job->getCompletedProjects())->toBe(0);
        expect($job->getStatus())->toBe(SyncJobStatus::Running);
        expect($job->getProviderId())->toBe($providerId);
        expect($job->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($job->getCompletedAt())->toBeNull();
    });

    it('creates a sync job without provider', function () {
        $job = SyncJob::create(totalProjects: 5);

        expect($job->getProviderId())->toBeNull();
        expect($job->getStatus())->toBe(SyncJobStatus::Running);
    });

    it('increments completed projects', function () {
        $job = SyncJob::create(totalProjects: 3);

        $job->incrementCompleted();
        expect($job->getCompletedProjects())->toBe(1);
        expect($job->getStatus())->toBe(SyncJobStatus::Running);

        $job->incrementCompleted();
        expect($job->getCompletedProjects())->toBe(2);
        expect($job->getStatus())->toBe(SyncJobStatus::Running);
    });

    it('auto-completes when all projects are done', function () {
        $job = SyncJob::create(totalProjects: 2);

        $job->incrementCompleted();
        $job->incrementCompleted();

        expect($job->getCompletedProjects())->toBe(2);
        expect($job->getStatus())->toBe(SyncJobStatus::Completed);
        expect($job->getCompletedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('marks as failed', function () {
        $job = SyncJob::create(totalProjects: 5);

        $job->markFailed();

        expect($job->getStatus())->toBe(SyncJobStatus::Failed);
        expect($job->getCompletedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });
});

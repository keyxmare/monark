<?php

declare(strict_types=1);

use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStatus;
use App\Sync\Domain\Model\GlobalSyncStep;

describe('GlobalSyncJob', function (): void {
    it('creates with running status and step 1', function (): void {
        $job = GlobalSyncJob::create();

        expect($job->getStatus())->toBe(GlobalSyncStatus::Running)
            ->and($job->getCurrentStep())->toBe(1)
            ->and($job->getCurrentStepName())->toBe('sync_projects')
            ->and($job->getStepProgress())->toBe(0)
            ->and($job->getStepTotal())->toBe(0)
            ->and($job->getCompletedAt())->toBeNull();
    });

    it('starts a step with correct total', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncVersions, 42);

        expect($job->getCurrentStep())->toBe(3)
            ->and($job->getCurrentStepName())->toBe('sync_versions')
            ->and($job->getStepTotal())->toBe(42)
            ->and($job->getStepProgress())->toBe(0);
    });

    it('increments progress', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncProjects, 5);
        $job->incrementProgress();
        $job->incrementProgress();

        expect($job->getStepProgress())->toBe(2);
    });

    it('completes with timestamp', function (): void {
        $job = GlobalSyncJob::create();
        $job->complete();

        expect($job->getStatus())->toBe(GlobalSyncStatus::Completed)
            ->and($job->getCompletedAt())->not->toBeNull();
    });

    it('marks failed', function (): void {
        $job = GlobalSyncJob::create();
        $job->markFailed();

        expect($job->getStatus())->toBe(GlobalSyncStatus::Failed);
    });

    it('returns completed step names', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncVersions, 10);

        expect($job->getCompletedStepNames())->toBe(['sync_projects', 'sync_coverage']);
    });

    it('returns all steps completed when done', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::ScanCve, 0);
        $job->complete();

        expect($job->getCompletedStepNames())->toBe(['sync_projects', 'sync_coverage', 'sync_versions', 'scan_cve']);
    });
});

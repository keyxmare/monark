<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(SyncJobRepositoryInterface::class);
});

describe('DoctrineSyncJobRepository', function () {
    it('saves and finds a sync job by id', function () {
        $job = SyncJob::create(totalProjects: 5);
        $this->repo->save($job);

        $found = $this->repo->findById($job->getId());
        expect($found)->not->toBeNull();
        expect($found->getTotalProjects())->toBe(5);
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(Uuid::v7()))->toBeNull();
    });
});

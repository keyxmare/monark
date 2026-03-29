<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(MergeRequestRepositoryInterface::class);
    $this->projectRepo = self::getContainer()->get(ProjectRepositoryInterface::class);

    $this->project = Project::create('P', 'p', null, 'https://git.com/p', 'main', ProjectVisibility::Private, Uuid::v7());
    $this->projectRepo->save($this->project);
});

function createMR(
    string $externalId,
    string $title,
    MergeRequestStatus $status,
    string $author,
    Project $project,
    ?DateTimeImmutable $mergedAt = null,
    ?DateTimeImmutable $closedAt = null,
): MergeRequest {
    return MergeRequest::create(
        externalId: $externalId,
        title: $title,
        description: null,
        sourceBranch: 'feature',
        targetBranch: 'main',
        status: $status,
        author: $author,
        url: "https://git.com/mr/{$externalId}",
        additions: null,
        deletions: null,
        reviewers: [],
        labels: [],
        mergedAt: $mergedAt,
        closedAt: $closedAt,
        project: $project,
    );
}

describe('DoctrineMergeRequestRepository', function () {
    it('saves and finds by id', function () {
        $mr = createMR('ext-1', 'Fix bug', MergeRequestStatus::Open, 'Alice', $this->project);
        $this->repo->save($mr);

        $found = $this->repo->findById($mr->getId());
        expect($found)->not->toBeNull();
        expect($found->getTitle())->toBe('Fix bug');
    });

    it('finds by project id with status filter', function () {
        $this->repo->save(createMR('e1', 'Open MR', MergeRequestStatus::Open, 'Alice', $this->project));
        $this->repo->save(createMR('e2', 'Merged MR', MergeRequestStatus::Merged, 'Bob', $this->project, new DateTimeImmutable()));
        $this->repo->save(createMR('e3', 'Another Open', MergeRequestStatus::Open, 'Charlie', $this->project));

        $openOnly = $this->repo->findByProjectId($this->project->getId(), 1, 20, [MergeRequestStatus::Open]);
        expect($openOnly)->toHaveCount(2);

        $merged = $this->repo->findByProjectId($this->project->getId(), 1, 20, [MergeRequestStatus::Merged]);
        expect($merged)->toHaveCount(1);
    });

    it('filters by author', function () {
        $this->repo->save(createMR('e1', 'MR1', MergeRequestStatus::Open, 'Alice', $this->project));
        $this->repo->save(createMR('e2', 'MR2', MergeRequestStatus::Open, 'Bob', $this->project));

        $aliceOnly = $this->repo->findByProjectId($this->project->getId(), 1, 20, [], 'Alice');
        expect($aliceOnly)->toHaveCount(1);
    });

    it('counts by project id with filters', function () {
        $this->repo->save(createMR('e1', 'MR1', MergeRequestStatus::Open, 'A', $this->project));
        $this->repo->save(createMR('e2', 'MR2', MergeRequestStatus::Closed, 'B', $this->project, closedAt: new DateTimeImmutable()));

        expect($this->repo->countByProjectId($this->project->getId()))->toBe(2);
        expect($this->repo->countByProjectId($this->project->getId(), [MergeRequestStatus::Open]))->toBe(1);
    });

    it('finds by external id and project', function () {
        $mr = createMR('unique-ext', 'Title', MergeRequestStatus::Open, 'A', $this->project);
        $this->repo->save($mr);

        $found = $this->repo->findByExternalIdAndProject('unique-ext', $this->project->getId());
        expect($found)->not->toBeNull();

        expect($this->repo->findByExternalIdAndProject('nonexistent', $this->project->getId()))->toBeNull();
    });

    it('deletes a merge request', function () {
        $mr = createMR('del', 'Del', MergeRequestStatus::Open, 'A', $this->project);
        $this->repo->save($mr);

        $this->repo->delete($mr);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($mr->getId()))->toBeNull();
    });
});

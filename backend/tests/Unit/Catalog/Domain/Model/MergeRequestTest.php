<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use Tests\Factory\Catalog\ProjectFactory;

describe('MergeRequest', function () {
    it('creates a merge request with all fields', function () {
        $project = ProjectFactory::create();
        $mergedAt = new \DateTimeImmutable('2026-03-10T14:00:00+00:00');

        $mr = MergeRequest::create(
            externalId: '42',
            title: 'feat: add login page',
            description: 'Implements the login page',
            sourceBranch: 'feature/login',
            targetBranch: 'main',
            status: MergeRequestStatus::Open,
            author: 'johndoe',
            url: 'https://gitlab.com/test/project/-/merge_requests/42',
            additions: 150,
            deletions: 20,
            reviewers: ['alice', 'bob'],
            labels: ['feature', 'frontend'],
            mergedAt: null,
            closedAt: null,
            project: $project,
        );

        expect($mr->getId())->not->toBeNull();
        expect($mr->getExternalId())->toBe('42');
        expect($mr->getTitle())->toBe('feat: add login page');
        expect($mr->getDescription())->toBe('Implements the login page');
        expect($mr->getSourceBranch())->toBe('feature/login');
        expect($mr->getTargetBranch())->toBe('main');
        expect($mr->getStatus())->toBe(MergeRequestStatus::Open);
        expect($mr->getAuthor())->toBe('johndoe');
        expect($mr->getUrl())->toBe('https://gitlab.com/test/project/-/merge_requests/42');
        expect($mr->getAdditions())->toBe(150);
        expect($mr->getDeletions())->toBe(20);
        expect($mr->getReviewers())->toBe(['alice', 'bob']);
        expect($mr->getLabels())->toBe(['feature', 'frontend']);
        expect($mr->getMergedAt())->toBeNull();
        expect($mr->getClosedAt())->toBeNull();
        expect($mr->getProject())->toBe($project);
        expect($mr->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($mr->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates a merge request with nullable fields', function () {
        $project = ProjectFactory::create();

        $mr = MergeRequest::create(
            externalId: '1',
            title: 'fix: typo',
            description: null,
            sourceBranch: 'fix/typo',
            targetBranch: 'main',
            status: MergeRequestStatus::Draft,
            author: 'jane',
            url: 'https://github.com/test/project/pull/1',
            additions: null,
            deletions: null,
            reviewers: [],
            labels: [],
            mergedAt: null,
            closedAt: null,
            project: $project,
        );

        expect($mr->getDescription())->toBeNull();
        expect($mr->getAdditions())->toBeNull();
        expect($mr->getDeletions())->toBeNull();
        expect($mr->getReviewers())->toBe([]);
        expect($mr->getLabels())->toBe([]);
        expect($mr->getStatus())->toBe(MergeRequestStatus::Draft);
    });

    it('updates fields selectively', function () {
        $project = ProjectFactory::create();

        $mr = MergeRequest::create(
            externalId: '10',
            title: 'WIP: dashboard',
            description: null,
            sourceBranch: 'feature/dashboard',
            targetBranch: 'main',
            status: MergeRequestStatus::Draft,
            author: 'dev',
            url: 'https://gitlab.com/test/project/-/merge_requests/10',
            additions: null,
            deletions: null,
            reviewers: [],
            labels: ['wip'],
            mergedAt: null,
            closedAt: null,
            project: $project,
        );

        $beforeUpdate = $mr->getUpdatedAt();
        \usleep(1000);

        $mr->update(
            title: 'feat: dashboard',
            status: MergeRequestStatus::Open,
            additions: 200,
            deletions: 50,
            reviewers: ['alice'],
            labels: ['feature'],
        );

        expect($mr->getTitle())->toBe('feat: dashboard');
        expect($mr->getStatus())->toBe(MergeRequestStatus::Open);
        expect($mr->getAdditions())->toBe(200);
        expect($mr->getDeletions())->toBe(50);
        expect($mr->getReviewers())->toBe(['alice']);
        expect($mr->getLabels())->toBe(['feature']);
        expect($mr->getSourceBranch())->toBe('feature/dashboard');
        expect($mr->getUpdatedAt())->not->toEqual($beforeUpdate);
    });

    it('updates merged status with timestamps', function () {
        $project = ProjectFactory::create();
        $mergedAt = new \DateTimeImmutable('2026-03-12T10:00:00+00:00');

        $mr = MergeRequest::create(
            externalId: '5',
            title: 'feat: something',
            description: null,
            sourceBranch: 'feature/something',
            targetBranch: 'main',
            status: MergeRequestStatus::Open,
            author: 'dev',
            url: 'https://gitlab.com/test/project/-/merge_requests/5',
            additions: 10,
            deletions: 5,
            reviewers: [],
            labels: [],
            mergedAt: null,
            closedAt: null,
            project: $project,
        );

        $mr->update(
            status: MergeRequestStatus::Merged,
            mergedAt: $mergedAt,
        );

        expect($mr->getStatus())->toBe(MergeRequestStatus::Merged);
        expect($mr->getMergedAt())->toBe($mergedAt);
    });
});

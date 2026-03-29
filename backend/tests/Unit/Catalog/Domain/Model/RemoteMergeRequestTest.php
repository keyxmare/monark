<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\RemoteMergeRequest;

describe('RemoteMergeRequest', function () {
    it('creates a remote merge request with all fields', function () {
        $mr = new RemoteMergeRequest(
            externalId: '99',
            title: 'feat: add dashboard',
            description: 'Implements the dashboard page',
            sourceBranch: 'feature/dashboard',
            targetBranch: 'main',
            status: 'open',
            author: 'johndoe',
            url: 'https://gitlab.com/test/project/-/merge_requests/99',
            additions: 200,
            deletions: 50,
            reviewers: ['alice', 'bob'],
            labels: ['feature', 'frontend'],
            createdAt: '2026-03-10T10:00:00+00:00',
            updatedAt: '2026-03-11T14:00:00+00:00',
            mergedAt: '2026-03-12T09:00:00+00:00',
            closedAt: null,
        );

        expect($mr->externalId)->toBe('99');
        expect($mr->title)->toBe('feat: add dashboard');
        expect($mr->description)->toBe('Implements the dashboard page');
        expect($mr->sourceBranch)->toBe('feature/dashboard');
        expect($mr->targetBranch)->toBe('main');
        expect($mr->status)->toBe('open');
        expect($mr->author)->toBe('johndoe');
        expect($mr->url)->toBe('https://gitlab.com/test/project/-/merge_requests/99');
        expect($mr->additions)->toBe(200);
        expect($mr->deletions)->toBe(50);
        expect($mr->reviewers)->toBe(['alice', 'bob']);
        expect($mr->labels)->toBe(['feature', 'frontend']);
        expect($mr->createdAt)->toBe('2026-03-10T10:00:00+00:00');
        expect($mr->updatedAt)->toBe('2026-03-11T14:00:00+00:00');
        expect($mr->mergedAt)->toBe('2026-03-12T09:00:00+00:00');
        expect($mr->closedAt)->toBeNull();
    });

    it('creates with nullable fields', function () {
        $mr = new RemoteMergeRequest(
            externalId: '1',
            title: 'fix: typo',
            description: null,
            sourceBranch: 'fix/typo',
            targetBranch: 'main',
            status: 'merged',
            author: 'jane',
            url: 'https://github.com/test/repo/pull/1',
            additions: null,
            deletions: null,
            reviewers: [],
            labels: [],
            createdAt: null,
            updatedAt: null,
            mergedAt: null,
            closedAt: null,
        );

        expect($mr->description)->toBeNull();
        expect($mr->additions)->toBeNull();
        expect($mr->deletions)->toBeNull();
        expect($mr->reviewers)->toBe([]);
        expect($mr->labels)->toBe([]);
        expect($mr->createdAt)->toBeNull();
        expect($mr->updatedAt)->toBeNull();
        expect($mr->mergedAt)->toBeNull();
        expect($mr->closedAt)->toBeNull();
    });
});

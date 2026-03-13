<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Infrastructure\GitProvider\GitLabClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\Factory\Catalog\ProviderFactory;

function gitlabProvider(): \App\Catalog\Domain\Model\Provider
{
    return ProviderFactory::create(
        name: 'GitLab',
        type: ProviderType::GitLab,
        url: 'https://gitlab.example.com',
        apiToken: 'glpat-test-token',
    );
}

describe('GitLabClient', function () {
    describe('getProject', function () {
        it('returns a RemoteProject from GitLab API', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 42,
                'name' => 'monark',
                'path_with_namespace' => 'team/monark',
                'description' => 'Dev hub',
                'http_url_to_repo' => 'https://gitlab.example.com/team/monark.git',
                'web_url' => 'https://gitlab.example.com/team/monark',
                'default_branch' => 'main',
                'visibility' => 'public',
                'avatar_url' => null,
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\gitlabProvider(), '42');

            expect($project->externalId)->toBe('42');
            expect($project->name)->toBe('monark');
            expect($project->slug)->toBe('team/monark');
            expect($project->description)->toBe('Dev hub');
            expect($project->defaultBranch)->toBe('main');
            expect($project->visibility)->toBe('public');
            expect($project->repositoryUrl)->toBe('https://gitlab.example.com/team/monark.git');
            expect($project->avatarUrl)->toBeNull();
            expect($mockResponse->getRequestUrl())->toContain('/api/v4/projects/42');
            expect($mockResponse->getRequestOptions()['normalized_headers']['private-token'][0] ?? '')->toContain('glpat-test-token');
        });

        it('handles private project with no description', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 99,
                'name' => 'secret',
                'path_with_namespace' => 'team/secret',
                'http_url_to_repo' => 'https://gitlab.example.com/team/secret.git',
                'default_branch' => 'develop',
                'visibility' => 'private',
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\gitlabProvider(), '99');

            expect($project->visibility)->toBe('private');
            expect($project->defaultBranch)->toBe('develop');
            expect($project->description)->toBeNull();
        });

        it('falls back to web_url when http_url_to_repo is missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 77,
                'name' => 'web-only',
                'path_with_namespace' => 'team/web-only',
                'web_url' => 'https://gitlab.example.com/team/web-only',
                'default_branch' => 'main',
                'visibility' => 'internal',
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\gitlabProvider(), '77');

            expect($project->repositoryUrl)->toBe('https://gitlab.example.com/team/web-only');
        });

        it('URL-encodes the external id', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'test',
                'path_with_namespace' => 'team/test',
                'http_url_to_repo' => 'https://gitlab.example.com/team/test.git',
                'default_branch' => 'main',
                'visibility' => 'private',
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->getProject(\gitlabProvider(), 'team/test');

            expect($mockResponse->getRequestUrl())->toContain('/api/v4/projects/team%2Ftest');
        });
    });

    describe('listMergeRequests', function () {
        it('maps GitLab merge requests correctly', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'iid' => 42,
                    'title' => 'feat: add login',
                    'description' => 'Login page implementation',
                    'source_branch' => 'feature/login',
                    'target_branch' => 'main',
                    'state' => 'opened',
                    'draft' => false,
                    'author' => ['username' => 'johndoe'],
                    'web_url' => 'https://gitlab.example.com/team/monark/-/merge_requests/42',
                    'reviewers' => [['username' => 'alice'], ['username' => 'bob']],
                    'labels' => ['feature', 'frontend'],
                    'created_at' => '2026-03-10T10:00:00Z',
                    'updated_at' => '2026-03-11T14:00:00Z',
                    'merged_at' => null,
                    'closed_at' => null,
                ],
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $mrs = $client->listMergeRequests(\gitlabProvider(), '42');

            expect($mrs)->toHaveCount(1);
            expect($mrs[0]->externalId)->toBe('42');
            expect($mrs[0]->title)->toBe('feat: add login');
            expect($mrs[0]->description)->toBe('Login page implementation');
            expect($mrs[0]->sourceBranch)->toBe('feature/login');
            expect($mrs[0]->targetBranch)->toBe('main');
            expect($mrs[0]->status)->toBe('open');
            expect($mrs[0]->author)->toBe('johndoe');
            expect($mrs[0]->reviewers)->toBe(['alice', 'bob']);
            expect($mrs[0]->labels)->toBe(['feature', 'frontend']);
            expect($mrs[0]->additions)->toBeNull();
            expect($mrs[0]->deletions)->toBeNull();
        });

        it('maps draft MRs to draft status', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'iid' => 10,
                    'title' => 'Draft: WIP feature',
                    'description' => null,
                    'source_branch' => 'wip/stuff',
                    'target_branch' => 'main',
                    'state' => 'opened',
                    'draft' => true,
                    'author' => ['username' => 'dev'],
                    'web_url' => 'https://gitlab.example.com/team/monark/-/merge_requests/10',
                    'reviewers' => [],
                    'labels' => [],
                    'created_at' => '2026-03-12T08:00:00Z',
                    'updated_at' => '2026-03-12T08:00:00Z',
                    'merged_at' => null,
                    'closed_at' => null,
                ],
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $mrs = $client->listMergeRequests(\gitlabProvider(), '42');

            expect($mrs[0]->status)->toBe('draft');
            expect($mrs[0]->description)->toBeNull();
        });

        it('maps merged state correctly', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'iid' => 5,
                    'title' => 'feat: done',
                    'description' => null,
                    'source_branch' => 'feature/done',
                    'target_branch' => 'main',
                    'state' => 'merged',
                    'draft' => false,
                    'author' => ['username' => 'dev'],
                    'web_url' => 'https://gitlab.example.com/team/monark/-/merge_requests/5',
                    'reviewers' => [],
                    'labels' => [],
                    'created_at' => '2026-03-10T10:00:00Z',
                    'updated_at' => '2026-03-11T10:00:00Z',
                    'merged_at' => '2026-03-11T10:00:00Z',
                    'closed_at' => null,
                ],
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $mrs = $client->listMergeRequests(\gitlabProvider(), '42');

            expect($mrs[0]->status)->toBe('merged');
            expect($mrs[0]->mergedAt)->toBe('2026-03-11T10:00:00Z');
        });

        it('includes url and dates in mapping', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'iid' => 42,
                    'title' => 'MR',
                    'source_branch' => 'feat',
                    'target_branch' => 'main',
                    'state' => 'opened',
                    'draft' => false,
                    'author' => ['username' => 'dev'],
                    'web_url' => 'https://gitlab.example.com/mr/42',
                    'reviewers' => [],
                    'labels' => [],
                    'created_at' => '2026-03-10T10:00:00Z',
                    'updated_at' => '2026-03-11T14:00:00Z',
                    'merged_at' => null,
                    'closed_at' => null,
                ],
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $mrs = $client->listMergeRequests(\gitlabProvider(), '42');

            expect($mrs[0]->url)->toBe('https://gitlab.example.com/mr/42');
            expect($mrs[0]->createdAt)->toBe('2026-03-10T10:00:00Z');
            expect($mrs[0]->updatedAt)->toBe('2026-03-11T14:00:00Z');
            expect($mrs[0]->mergedAt)->toBeNull();
            expect($mrs[0]->closedAt)->toBeNull();
        });

        it('handles MR with missing author', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'iid' => 1,
                    'title' => 'No Author MR',
                    'source_branch' => 'fix',
                    'target_branch' => 'main',
                    'state' => 'opened',
                    'draft' => false,
                    'web_url' => 'https://gitlab.example.com/mr/1',
                    'reviewers' => [],
                    'labels' => [],
                    'created_at' => '2026-03-12T08:00:00Z',
                    'updated_at' => '2026-03-12T08:00:00Z',
                    'merged_at' => null,
                    'closed_at' => null,
                ],
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $mrs = $client->listMergeRequests(\gitlabProvider(), '42');

            expect($mrs[0]->author)->toBe('');
        });

        it('passes state filter as GitLab format', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\gitlabProvider(), '42', 'open', 2, 10);

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('state=opened');
            expect($url)->toContain('page=2');
            expect($url)->toContain('per_page=10');
            expect($url)->toContain('order_by=updated_at');
            expect($url)->toContain('sort=desc');
        });

        it('returns empty array for project with no MRs', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $mrs = $client->listMergeRequests(\gitlabProvider(), '42');

            expect($mrs)->toBeEmpty();
        });
    });
});

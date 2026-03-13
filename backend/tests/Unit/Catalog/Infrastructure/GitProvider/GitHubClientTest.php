<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Infrastructure\GitProvider\GitHubClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\Factory\Catalog\ProviderFactory;

function githubProvider(): \App\Catalog\Domain\Model\Provider
{
    return ProviderFactory::create(
        name: 'GitHub',
        type: ProviderType::GitHub,
        url: 'https://api.github.com',
        apiToken: 'ghp_test-token',
        username: 'keyxmare',
    );
}

function githubProviderPublicOnly(): \App\Catalog\Domain\Model\Provider
{
    return ProviderFactory::create(
        name: 'GitHub Public',
        type: ProviderType::GitHub,
        url: 'https://api.github.com',
        apiToken: null,
        username: 'keyxmare',
    );
}

describe('GitHubClient', function () {
    describe('listProjects (authenticated)', function () {
        it('maps GitHub repos to RemoteProject list', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'id' => 123,
                    'name' => 'monark',
                    'full_name' => 'keyxmare/monark',
                    'description' => 'Dev hub',
                    'clone_url' => 'https://github.com/keyxmare/monark.git',
                    'html_url' => 'https://github.com/keyxmare/monark',
                    'default_branch' => 'main',
                    'private' => false,
                    'owner' => ['avatar_url' => 'https://avatars.githubusercontent.com/u/1'],
                ],
                [
                    'id' => 456,
                    'name' => 'private-repo',
                    'full_name' => 'keyxmare/private-repo',
                    'description' => null,
                    'clone_url' => 'https://github.com/keyxmare/private-repo.git',
                    'html_url' => 'https://github.com/keyxmare/private-repo',
                    'default_branch' => 'develop',
                    'private' => true,
                    'owner' => ['avatar_url' => 'https://avatars.githubusercontent.com/u/1'],
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $projects = $client->listProjects(\githubProvider(), 1, 20);

            expect($projects)->toHaveCount(2);
            expect($projects[0]->externalId)->toBe('keyxmare/monark');
            expect($projects[0]->name)->toBe('monark');
            expect($projects[0]->slug)->toBe('keyxmare/monark');
            expect($projects[0]->description)->toBe('Dev hub');
            expect($projects[0]->repositoryUrl)->toBe('https://github.com/keyxmare/monark.git');
            expect($projects[0]->defaultBranch)->toBe('main');
            expect($projects[0]->visibility)->toBe('public');
            expect($projects[0]->avatarUrl)->toBe('https://avatars.githubusercontent.com/u/1');
            expect($projects[1]->visibility)->toBe('private');
            expect($projects[1]->defaultBranch)->toBe('develop');
            expect($projects[1]->description)->toBeNull();
        });

        it('uses /user/repos with auth headers', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 2, 50);

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/user/repos');
            expect($url)->toContain('type=owner');
            expect($url)->toContain('page=2');
            expect($url)->toContain('per_page=50');
            expect($url)->toContain('sort=full_name');
            expect($url)->toContain('direction=asc');
        });

        it('caps per_page at 100', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 200);

            expect($mockResponse->getRequestUrl())->toContain('per_page=100');
        });
    });

    describe('listProjects (public / no token)', function () {
        it('uses /users/{username}/repos without auth', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'id' => 789,
                    'name' => 'open-source',
                    'full_name' => 'keyxmare/open-source',
                    'description' => 'OSS project',
                    'clone_url' => 'https://github.com/keyxmare/open-source.git',
                    'html_url' => 'https://github.com/keyxmare/open-source',
                    'default_branch' => 'main',
                    'private' => false,
                    'owner' => ['avatar_url' => 'https://avatars.githubusercontent.com/u/1'],
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $projects = $client->listProjects(\githubProviderPublicOnly());

            expect($projects)->toHaveCount(1);
            expect($projects[0]->name)->toBe('open-source');
            expect($projects[0]->repositoryUrl)->toBe('https://github.com/keyxmare/open-source.git');
            expect($projects[0]->avatarUrl)->toBe('https://avatars.githubusercontent.com/u/1');
            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/users/keyxmare/repos');
            expect($url)->not->toContain('type=owner');
            expect($url)->toContain('sort=');
            expect($url)->toContain('direction=');
            expect($url)->toContain('page=');
            expect($url)->toContain('per_page=');
        });
    });

    describe('countProjects', function () {
        it('returns sum of public and owned private repos when authenticated', function () {
            $mockResponse = new MockResponse(\json_encode([
                'login' => 'keyxmare',
                'public_repos' => 12,
                'owned_private_repos' => 5,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider());

            expect($count)->toBe(17);
            expect($mockResponse->getRequestUrl())->toContain('/user');
        });

        it('returns only private repos when visibility filter is private', function () {
            $mockResponse = new MockResponse(\json_encode([
                'login' => 'keyxmare',
                'public_repos' => 12,
                'owned_private_repos' => 5,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider(), null, 'private');

            expect($count)->toBe(5);
        });

        it('returns only public repos when visibility filter is public', function () {
            $mockResponse = new MockResponse(\json_encode([
                'login' => 'keyxmare',
                'public_repos' => 12,
                'owned_private_repos' => 5,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider(), null, 'public');

            expect($count)->toBe(12);
        });

        it('uses search API for count with search term', function () {
            $mockResponse = new MockResponse(\json_encode([
                'total_count' => 3,
                'items' => [],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider(), 'monark');

            expect($count)->toBe(3);
            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/search/repositories');
            expect($url)->toContain('per_page=1');
            expect($url)->toContain('monark');
            expect($url)->toContain('user:@me');
        });

        it('uses search API with visibility filter', function () {
            $mockResponse = new MockResponse(\json_encode([
                'total_count' => 2,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider(), 'monark', 'private');

            expect($count)->toBe(2);
            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('is:private');
        });

        it('returns only public repos count when no token', function () {
            $mockResponse = new MockResponse(\json_encode([
                'login' => 'keyxmare',
                'public_repos' => 8,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProviderPublicOnly());

            expect($count)->toBe(8);
            expect($mockResponse->getRequestUrl())->toContain('/users/keyxmare');
        });
    });

    describe('testConnection', function () {
        it('returns true on 200 response with token', function () {
            $mockResponse = new MockResponse(\json_encode(['login' => 'keyxmare']), ['http_code' => 200]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\githubProvider()))->toBeTrue();
        });

        it('verifies username exists when no token', function () {
            $mockResponse = new MockResponse(\json_encode(['login' => 'keyxmare']), ['http_code' => 200]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\githubProviderPublicOnly()))->toBeTrue();
            expect($mockResponse->getRequestUrl())->toContain('/users/keyxmare');
        });

        it('returns false on exception', function () {
            $client = new GitHubClient(new MockHttpClient(function () {
                throw new \RuntimeException('Unauthorized');
            }));

            expect($client->testConnection(\githubProvider()))->toBeFalse();
        });
    });

    describe('getFileContent', function () {
        it('decodes base64 file content', function () {
            $content = 'Hello World';
            $mockResponse = new MockResponse(\json_encode([
                'content' => \base64_encode($content),
                'encoding' => 'base64',
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $result = $client->getFileContent(\githubProviderPublicOnly(), 'keyxmare/monark', 'README.md');

            expect($result)->toBe('Hello World');
            expect($mockResponse->getRequestUrl())->toContain('/repos/keyxmare/monark/contents/README.md');
            expect($mockResponse->getRequestUrl())->toContain('ref=main');
        });

        it('returns null on 404', function () {
            $mockResponse = new MockResponse('', ['http_code' => 404]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $result = $client->getFileContent(\githubProviderPublicOnly(), 'keyxmare/monark', 'nonexistent.txt');

            expect($result)->toBeNull();
        });
    });

    describe('getProject', function () {
        it('returns a RemoteProject from GitHub API', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 123,
                'name' => 'monark',
                'full_name' => 'keyxmare/monark',
                'description' => 'Dev hub',
                'clone_url' => 'https://github.com/keyxmare/monark.git',
                'html_url' => 'https://github.com/keyxmare/monark',
                'default_branch' => 'main',
                'private' => false,
                'owner' => ['avatar_url' => 'https://avatars.githubusercontent.com/u/1'],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/monark');

            expect($project->externalId)->toBe('keyxmare/monark');
            expect($project->name)->toBe('monark');
            expect($project->description)->toBe('Dev hub');
            expect($project->defaultBranch)->toBe('main');
            expect($project->visibility)->toBe('public');
            expect($project->repositoryUrl)->toBe('https://github.com/keyxmare/monark.git');
            expect($project->avatarUrl)->toBe('https://avatars.githubusercontent.com/u/1');
            expect($project->slug)->toBe('keyxmare/monark');
            expect($mockResponse->getRequestUrl())->toContain('/repos/keyxmare/monark');
        });

        it('maps private repos correctly', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 456,
                'name' => 'secret',
                'full_name' => 'keyxmare/secret',
                'description' => null,
                'clone_url' => 'https://github.com/keyxmare/secret.git',
                'html_url' => 'https://github.com/keyxmare/secret',
                'default_branch' => 'develop',
                'private' => true,
                'owner' => ['avatar_url' => 'https://avatars.githubusercontent.com/u/1'],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/secret');

            expect($project->visibility)->toBe('private');
            expect($project->defaultBranch)->toBe('develop');
            expect($project->description)->toBeNull();
        });
    });

    describe('listDirectory', function () {
        it('normalizes GitHub types to tree/blob', function () {
            $mockResponse = new MockResponse(\json_encode([
                ['name' => 'src', 'type' => 'dir', 'path' => 'src'],
                ['name' => 'README.md', 'type' => 'file', 'path' => 'README.md'],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $items = $client->listDirectory(\githubProviderPublicOnly(), 'keyxmare/monark');

            expect($items)->toHaveCount(2);
            expect($items[0])->toBe(['name' => 'src', 'type' => 'tree', 'path' => 'src']);
            expect($items[1])->toBe(['name' => 'README.md', 'type' => 'blob', 'path' => 'README.md']);
        });

        it('handles single file response', function () {
            $mockResponse = new MockResponse(\json_encode([
                'name' => 'main.ts',
                'type' => 'file',
                'path' => 'src/main.ts',
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $items = $client->listDirectory(\githubProviderPublicOnly(), 'keyxmare/monark', 'src/main.ts');

            expect($items)->toHaveCount(1);
            expect($items[0]['name'])->toBe('main.ts');
        });

        it('returns empty array on error', function () {
            $mockResponse = new MockResponse('', ['http_code' => 404]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $items = $client->listDirectory(\githubProviderPublicOnly(), 'keyxmare/monark', 'nonexistent');

            expect($items)->toBeEmpty();
        });
    });

    describe('listMergeRequests', function () {
        it('maps GitHub pull requests correctly', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'number' => 42,
                    'title' => 'feat: add login',
                    'body' => 'Login page implementation',
                    'state' => 'open',
                    'draft' => false,
                    'head' => ['ref' => 'feature/login'],
                    'base' => ['ref' => 'main'],
                    'user' => ['login' => 'octocat'],
                    'html_url' => 'https://github.com/keyxmare/monark/pull/42',
                    'additions' => 150,
                    'deletions' => 20,
                    'requested_reviewers' => [['login' => 'alice'], ['login' => 'bob']],
                    'labels' => [['name' => 'feature'], ['name' => 'frontend']],
                    'created_at' => '2026-03-10T10:00:00Z',
                    'updated_at' => '2026-03-11T14:00:00Z',
                    'merged_at' => null,
                    'closed_at' => null,
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $prs = $client->listMergeRequests(\githubProvider(), 'keyxmare/monark');

            expect($prs)->toHaveCount(1);
            expect($prs[0]->externalId)->toBe('42');
            expect($prs[0]->title)->toBe('feat: add login');
            expect($prs[0]->description)->toBe('Login page implementation');
            expect($prs[0]->sourceBranch)->toBe('feature/login');
            expect($prs[0]->targetBranch)->toBe('main');
            expect($prs[0]->status)->toBe('open');
            expect($prs[0]->author)->toBe('octocat');
            expect($prs[0]->url)->toBe('https://github.com/keyxmare/monark/pull/42');
            expect($prs[0]->additions)->toBe(150);
            expect($prs[0]->deletions)->toBe(20);
            expect($prs[0]->reviewers)->toBe(['alice', 'bob']);
            expect($prs[0]->labels)->toBe(['feature', 'frontend']);
            expect($prs[0]->createdAt)->toBe('2026-03-10T10:00:00Z');
            expect($prs[0]->updatedAt)->toBe('2026-03-11T14:00:00Z');
            expect($prs[0]->mergedAt)->toBeNull();
            expect($prs[0]->closedAt)->toBeNull();
        });

        it('maps merged PR via merged_at field', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'number' => 10,
                    'title' => 'feat: done',
                    'body' => null,
                    'state' => 'closed',
                    'draft' => false,
                    'head' => ['ref' => 'feature/done'],
                    'base' => ['ref' => 'main'],
                    'user' => ['login' => 'dev'],
                    'html_url' => 'https://github.com/keyxmare/monark/pull/10',
                    'additions' => 50,
                    'deletions' => 10,
                    'requested_reviewers' => [],
                    'labels' => [],
                    'created_at' => '2026-03-10T10:00:00Z',
                    'updated_at' => '2026-03-11T10:00:00Z',
                    'merged_at' => '2026-03-11T10:00:00Z',
                    'closed_at' => '2026-03-11T10:00:00Z',
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $prs = $client->listMergeRequests(\githubProvider(), 'keyxmare/monark');

            expect($prs[0]->status)->toBe('merged');
            expect($prs[0]->mergedAt)->toBe('2026-03-11T10:00:00Z');
        });

        it('maps draft PRs to draft status', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'number' => 3,
                    'title' => 'Draft: WIP',
                    'body' => null,
                    'state' => 'open',
                    'draft' => true,
                    'head' => ['ref' => 'wip/stuff'],
                    'base' => ['ref' => 'main'],
                    'user' => ['login' => 'dev'],
                    'html_url' => 'https://github.com/keyxmare/monark/pull/3',
                    'requested_reviewers' => [],
                    'labels' => [],
                    'created_at' => '2026-03-12T08:00:00Z',
                    'updated_at' => '2026-03-12T08:00:00Z',
                    'merged_at' => null,
                    'closed_at' => null,
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $prs = $client->listMergeRequests(\githubProvider(), 'keyxmare/monark');

            expect($prs[0]->status)->toBe('draft');
        });

        it('passes state filter correctly', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', 'open', 2, 10);

            expect($mockResponse->getRequestUrl())->toContain('/repos/keyxmare/monark/pulls');
            expect($mockResponse->getRequestUrl())->toContain('state=open');
            expect($mockResponse->getRequestUrl())->toContain('page=2');
            expect($mockResponse->getRequestUrl())->toContain('per_page=10');
        });

        it('maps closed (not merged) PR correctly', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'number' => 7,
                    'title' => 'feat: rejected',
                    'body' => 'Was rejected',
                    'state' => 'closed',
                    'draft' => false,
                    'head' => ['ref' => 'feature/nope'],
                    'base' => ['ref' => 'main'],
                    'user' => ['login' => 'dev'],
                    'html_url' => 'https://github.com/keyxmare/monark/pull/7',
                    'additions' => 5,
                    'deletions' => 2,
                    'requested_reviewers' => [],
                    'labels' => [['name' => 'wontfix']],
                    'created_at' => '2026-03-10T10:00:00Z',
                    'updated_at' => '2026-03-11T10:00:00Z',
                    'merged_at' => null,
                    'closed_at' => '2026-03-11T10:00:00Z',
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $prs = $client->listMergeRequests(\githubProvider(), 'keyxmare/monark');

            expect($prs[0]->status)->toBe('closed');
            expect($prs[0]->mergedAt)->toBeNull();
            expect($prs[0]->closedAt)->toBe('2026-03-11T10:00:00Z');
            expect($prs[0]->additions)->toBe(5);
            expect($prs[0]->deletions)->toBe(2);
        });

        it('maps PR with missing optional fields', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'number' => 99,
                    'title' => 'minimal PR',
                    'body' => null,
                    'state' => 'open',
                    'draft' => false,
                    'head' => ['ref' => 'fix/min'],
                    'base' => ['ref' => 'main'],
                    'user' => ['login' => 'bot'],
                    'html_url' => 'https://github.com/keyxmare/monark/pull/99',
                    'requested_reviewers' => [],
                    'labels' => [],
                    'created_at' => '2026-03-12T08:00:00Z',
                    'updated_at' => '2026-03-12T08:00:00Z',
                    'merged_at' => null,
                    'closed_at' => null,
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $prs = $client->listMergeRequests(\githubProvider(), 'keyxmare/monark');

            expect($prs[0]->description)->toBeNull();
            expect($prs[0]->additions)->toBeNull();
            expect($prs[0]->deletions)->toBeNull();
            expect($prs[0]->reviewers)->toBe([]);
            expect($prs[0]->labels)->toBe([]);
            expect($prs[0]->author)->toBe('bot');
        });

        it('passes updatedAfter filter in URL', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $after = new \DateTimeImmutable('2026-03-10T10:00:00+00:00');
            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', null, 1, 20, $after);

            expect($mockResponse->getRequestUrl())->toContain('sort=updated');
            expect($mockResponse->getRequestUrl())->toContain('direction=desc');
        });

        it('returns empty array for repo with no PRs', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $prs = $client->listMergeRequests(\githubProvider(), 'keyxmare/monark');

            expect($prs)->toBeEmpty();
        });
    });
});

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

function githubProviderEmptyToken(): \App\Catalog\Domain\Model\Provider
{
    return ProviderFactory::create(
        name: 'GitHub Empty Token',
        type: ProviderType::GitHub,
        url: 'https://api.github.com',
        apiToken: '',
        username: 'keyxmare',
    );
}

function githubProviderGithubDotCom(): \App\Catalog\Domain\Model\Provider
{
    return ProviderFactory::create(
        name: 'GitHub.com',
        type: ProviderType::GitHub,
        url: 'https://github.com',
        apiToken: 'ghp_test-token',
        username: 'keyxmare',
    );
}

function githubProviderGithubDotComTrailingSlash(): \App\Catalog\Domain\Model\Provider
{
    return ProviderFactory::create(
        name: 'GitHub.com trailing slash',
        type: ProviderType::GitHub,
        url: 'https://github.com/',
        apiToken: 'ghp_test-token',
        username: 'keyxmare',
    );
}

function githubProviderCustomUrl(): \App\Catalog\Domain\Model\Provider
{
    return ProviderFactory::create(
        name: 'GitHub Enterprise',
        type: ProviderType::GitHub,
        url: 'https://github.corp.example.com/api/v3',
        apiToken: 'ghp_enterprise-token',
        username: 'keyxmare',
    );
}

describe('GitHubClient', function () {
    describe('baseUrl and headers', function () {
        it('converts github.com URL to api.github.com', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProviderGithubDotCom());

            $url = $mockResponse->getRequestUrl();
            expect($url)->toStartWith('https://api.github.com/');
            expect($url)->toContain('api.github.com/user/repos');
        });

        it('uses custom URL as-is for GitHub Enterprise', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProviderCustomUrl());

            $url = $mockResponse->getRequestUrl();
            expect($url)->toStartWith('https://github.corp.example.com/api/v3/');
        });

        it('sends Authorization Bearer header when authenticated', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider());

            $options = $mockResponse->getRequestOptions();
            $authHeader = $options['normalized_headers']['authorization'][0] ?? '';
            expect($authHeader)->toContain('Bearer ghp_test-token');
        });

        it('sends GitHub-specific Accept and API version headers', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider());

            $options = $mockResponse->getRequestOptions();
            $acceptHeader = $options['normalized_headers']['accept'][0] ?? '';
            $versionHeader = $options['normalized_headers']['x-github-api-version'][0] ?? '';
            expect($acceptHeader)->toContain('application/vnd.github+json');
            expect($versionHeader)->toContain('2022-11-28');
        });

        it('does not send Authorization header when no token', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProviderPublicOnly());

            $options = $mockResponse->getRequestOptions();
            expect($options['normalized_headers']['authorization'] ?? null)->toBeNull();
        });

        it('treats empty string token as unauthenticated', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProviderEmptyToken());

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/users/keyxmare/repos');
            expect($url)->not->toContain('/user/repos');
            $options = $mockResponse->getRequestOptions();
            expect($options['normalized_headers']['authorization'] ?? null)->toBeNull();
        });
    });

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

        it('uses /user/repos with auth headers and correct query params', function () {
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
            expect($mockResponse->getRequestUrl())->not->toContain('per_page=200');
        });

        it('does not cap per_page when already under 100', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 50);

            expect($mockResponse->getRequestUrl())->toContain('per_page=50');
        });

        it('includes visibility in query when provided', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, null, 'private');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('visibility=private');
            expect($url)->toContain('/user/repos');
        });

        it('does not include visibility when null', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, null, null);

            expect($mockResponse->getRequestUrl())->not->toContain('visibility=');
        });

        it('does not include visibility when empty string', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, null, '');

            expect($mockResponse->getRequestUrl())->not->toContain('visibility=');
        });

        it('maps sort=name to full_name for GitHub', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, null, null, 'name', 'asc');

            expect($mockResponse->getRequestUrl())->toContain('sort=full_name');
        });

        it('maps non-name sort to updated for GitHub', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, null, null, 'updated_at', 'desc');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('sort=updated');
            expect($url)->toContain('direction=desc');
        });

        it('forwards sortDir parameter correctly', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, null, null, 'name', 'desc');

            expect($mockResponse->getRequestUrl())->toContain('direction=desc');
        });

        it('returns empty array for no results', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $projects = $client->listProjects(\githubProvider());

            expect($projects)->toBeEmpty();
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
            expect($url)->toContain('sort=full_name');
            expect($url)->toContain('direction=asc');
            expect($url)->toContain('page=1');
            expect($url)->toContain('per_page=20');
        });

        it('does not include visibility in unauthenticated query', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProviderPublicOnly(), 1, 20, null, 'private');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/users/keyxmare/repos');
            expect($url)->not->toContain('visibility=');
        });
    });

    describe('listProjects (search)', function () {
        it('uses search API when search is non-empty', function () {
            $mockResponse = new MockResponse(\json_encode([
                'total_count' => 1,
                'items' => [
                    [
                        'name' => 'monark',
                        'full_name' => 'keyxmare/monark',
                        'description' => 'Dev hub',
                        'clone_url' => 'https://github.com/keyxmare/monark.git',
                        'html_url' => 'https://github.com/keyxmare/monark',
                        'default_branch' => 'main',
                        'private' => false,
                        'owner' => ['avatar_url' => 'https://avatars.githubusercontent.com/u/1'],
                    ],
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $projects = $client->listProjects(\githubProvider(), 1, 20, 'monark');

            expect($projects)->toHaveCount(1);
            expect($projects[0]->name)->toBe('monark');
            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/search/repositories');
            expect($url)->toContain('user:@me');
        });

        it('does not use search API when search is null', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, null);

            expect($mockResponse->getRequestUrl())->not->toContain('/search/repositories');
            expect($mockResponse->getRequestUrl())->toContain('/user/repos');
        });

        it('does not use search API when search is empty string', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, '');

            expect($mockResponse->getRequestUrl())->not->toContain('/search/repositories');
            expect($mockResponse->getRequestUrl())->toContain('/user/repos');
        });

        it('uses user:{username} qualifier when unauthenticated', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProviderPublicOnly(), 1, 20, 'test');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/search/repositories');
            expect($url)->toContain('user:keyxmare');
            expect($url)->not->toContain('user:@me');
        });

        it('appends visibility filter to search query', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, 'test', 'private');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('is:private');
        });

        it('does not append visibility filter when null', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, 'test', null);

            $url = $mockResponse->getRequestUrl();
            expect($url)->not->toContain('is:');
        });

        it('does not append visibility filter when empty string', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, 'test', '');

            $url = $mockResponse->getRequestUrl();
            expect($url)->not->toContain('is:');
        });

        it('maps sort=name to stars for search', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, 'test', null, 'name', 'asc');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('sort=stars');
        });

        it('maps non-name sort to updated for search', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 20, 'test', null, 'updated_at', 'desc');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('sort=updated');
            expect($url)->toContain('order=desc');
        });

        it('forwards page and perPage to search', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 3, 50, 'test');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('page=3');
            expect($url)->toContain('per_page=50');
        });

        it('caps perPage at 100 in search', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listProjects(\githubProvider(), 1, 200, 'test');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('per_page=100');
            expect($url)->not->toContain('per_page=200');
        });

        it('returns empty array when search has no results', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0, 'items' => []]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $projects = $client->listProjects(\githubProvider(), 1, 20, 'nonexistent');

            expect($projects)->toBeEmpty();
        });

        it('returns empty array when items key is missing from search response', function () {
            $mockResponse = new MockResponse(\json_encode(['total_count' => 0]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $projects = $client->listProjects(\githubProvider(), 1, 20, 'test');

            expect($projects)->toBeEmpty();
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
            expect($count)->not->toBe(12);
            expect($count)->not->toBe(17);
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
            expect($count)->not->toBe(5);
            expect($count)->not->toBe(17);
        });

        it('returns sum when visibility is neither private nor public', function () {
            $mockResponse = new MockResponse(\json_encode([
                'login' => 'keyxmare',
                'public_repos' => 12,
                'owned_private_repos' => 5,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider(), null, null);

            expect($count)->toBe(17);
        });

        it('returns 0 when API does not have public_repos or owned_private_repos', function () {
            $mockResponse = new MockResponse(\json_encode([
                'login' => 'keyxmare',
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider());

            expect($count)->toBe(0);
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

        it('uses user:{username} in search count when unauthenticated', function () {
            $mockResponse = new MockResponse(\json_encode([
                'total_count' => 2,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProviderPublicOnly(), 'test');

            expect($count)->toBe(2);
            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('user:keyxmare');
            expect($url)->not->toContain('user:@me');
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

        it('does not append visibility to search when null', function () {
            $mockResponse = new MockResponse(\json_encode([
                'total_count' => 1,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $client->countProjects(\githubProvider(), 'test', null);

            expect($mockResponse->getRequestUrl())->not->toContain('is%3A');
        });

        it('does not append visibility to search when empty string', function () {
            $mockResponse = new MockResponse(\json_encode([
                'total_count' => 1,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $client->countProjects(\githubProvider(), 'test', '');

            expect($mockResponse->getRequestUrl())->not->toContain('is%3A');
        });

        it('does not use search API when search is empty string', function () {
            $mockResponse = new MockResponse(\json_encode([
                'public_repos' => 10,
                'owned_private_repos' => 3,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider(), '');

            expect($count)->toBe(13);
            expect($mockResponse->getRequestUrl())->not->toContain('/search/repositories');
            expect($mockResponse->getRequestUrl())->toContain('/user');
        });

        it('returns 0 when total_count is missing from search response', function () {
            $mockResponse = new MockResponse(\json_encode([]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProvider(), 'test');

            expect($count)->toBe(0);
        });

        it('returns only public repos count when no token', function () {
            $mockResponse = new MockResponse(\json_encode([
                'login' => 'keyxmare',
                'public_repos' => 8,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProviderPublicOnly());

            expect($count)->toBe(8);
            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/users/keyxmare');
            expect($url)->not->toMatch('#/user[^s]#');
        });

        it('returns 0 when unauthenticated and public_repos missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'login' => 'keyxmare',
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\githubProviderPublicOnly());

            expect($count)->toBe(0);
        });
    });

    describe('testConnection', function () {
        it('returns true on 200 response with token', function () {
            $mockResponse = new MockResponse(\json_encode(['login' => 'keyxmare']), ['http_code' => 200]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\githubProvider()))->toBeTrue();
            expect($mockResponse->getRequestUrl())->toContain('/user');
            expect($mockResponse->getRequestUrl())->not->toContain('/users/');
        });

        it('returns false on non-200 response', function () {
            $mockResponse = new MockResponse('', ['http_code' => 401]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\githubProvider()))->toBeFalse();
        });

        it('returns false on 201 response (not exactly 200)', function () {
            $mockResponse = new MockResponse('', ['http_code' => 201]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\githubProvider()))->toBeFalse();
        });

        it('verifies username exists when no token', function () {
            $mockResponse = new MockResponse(\json_encode(['login' => 'keyxmare']), ['http_code' => 200]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\githubProviderPublicOnly()))->toBeTrue();
            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/users/keyxmare');
            expect($url)->not->toMatch('#/user[^s]#');
        });

        it('returns false on exception', function () {
            $client = new GitHubClient(new MockHttpClient(function () {
                throw new \RuntimeException('Unauthorized');
            }));

            expect($client->testConnection(\githubProvider()))->toBeFalse();
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

        it('falls back to name when full_name is missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'orphan',
                'clone_url' => 'https://github.com/keyxmare/orphan.git',
                'default_branch' => 'main',
                'private' => false,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/orphan');

            expect($project->externalId)->toBe('orphan');
            expect($project->slug)->toBe('orphan');
        });

        it('falls back to html_url when clone_url is missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'nohtml',
                'full_name' => 'keyxmare/nohtml',
                'html_url' => 'https://github.com/keyxmare/nohtml',
                'default_branch' => 'main',
                'private' => false,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/nohtml');

            expect($project->repositoryUrl)->toBe('https://github.com/keyxmare/nohtml');
        });

        it('returns empty string repositoryUrl when both clone_url and html_url missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'bare',
                'full_name' => 'keyxmare/bare',
                'default_branch' => 'main',
                'private' => false,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/bare');

            expect($project->repositoryUrl)->toBe('');
        });

        it('defaults to main when default_branch missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'nobranch',
                'full_name' => 'keyxmare/nobranch',
                'clone_url' => 'https://github.com/keyxmare/nobranch.git',
                'private' => false,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/nobranch');

            expect($project->defaultBranch)->toBe('main');
        });

        it('returns null avatarUrl when owner is missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'noowner',
                'full_name' => 'keyxmare/noowner',
                'clone_url' => 'https://github.com/keyxmare/noowner.git',
                'default_branch' => 'main',
                'private' => false,
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/noowner');

            expect($project->avatarUrl)->toBeNull();
        });

        it('returns empty string avatarUrl when owner has no avatar_url', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'noavatar',
                'full_name' => 'keyxmare/noavatar',
                'clone_url' => 'https://github.com/keyxmare/noavatar.git',
                'default_branch' => 'main',
                'private' => false,
                'owner' => [],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/noavatar');

            expect($project->avatarUrl)->toBe('');
        });

        it('defaults visibility to public when private key is missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'noprivate',
                'full_name' => 'keyxmare/noprivate',
                'clone_url' => 'https://github.com/keyxmare/noprivate.git',
                'default_branch' => 'main',
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\githubProvider(), 'keyxmare/noprivate');

            expect($project->visibility)->toBe('public');
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

        it('uses custom ref parameter', function () {
            $mockResponse = new MockResponse(\json_encode([
                'content' => \base64_encode('content'),
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $client->getFileContent(\githubProvider(), 'keyxmare/monark', 'file.txt', 'develop');

            expect($mockResponse->getRequestUrl())->toContain('ref=develop');
            expect($mockResponse->getRequestUrl())->not->toContain('ref=main');
        });

        it('returns null when content key is missing', function () {
            $mockResponse = new MockResponse(\json_encode([]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $result = $client->getFileContent(\githubProvider(), 'keyxmare/monark', 'file.txt');

            expect($result)->toBeNull();
        });

        it('returns null on 404', function () {
            $mockResponse = new MockResponse('', ['http_code' => 404]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $result = $client->getFileContent(\githubProviderPublicOnly(), 'keyxmare/monark', 'nonexistent.txt');

            expect($result)->toBeNull();
        });

        it('returns null on 403 (rate limit or no access)', function () {
            $mockResponse = new MockResponse(\json_encode(['message' => 'API rate limit exceeded']), ['http_code' => 403]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $result = $client->getFileContent(\githubProviderPublicOnly(), 'keyxmare/monark', 'composer.json');

            expect($result)->toBeNull();
        });

        it('throws on other HTTP errors (e.g. 500)', function () {
            $mockResponse = new MockResponse('', ['http_code' => 500]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $threw = false;
            try {
                $client->getFileContent(\githubProvider(), 'keyxmare/monark', 'file.txt');
            } catch (\Throwable) {
                $threw = true;
            }
            expect($threw)->toBeTrue();
        });

        it('builds correct URL with externalProjectId and filePath', function () {
            $mockResponse = new MockResponse(\json_encode([
                'content' => \base64_encode('test'),
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $client->getFileContent(\githubProvider(), 'org/repo', 'src/main.ts', 'v2');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/repos/org/repo/contents/src/main.ts');
            expect($url)->toContain('ref=v2');
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

        it('preserves unknown type values', function () {
            $mockResponse = new MockResponse(\json_encode([
                ['name' => 'link', 'type' => 'symlink', 'path' => 'link'],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $items = $client->listDirectory(\githubProviderPublicOnly(), 'keyxmare/monark');

            expect($items[0]['type'])->toBe('symlink');
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
            expect($items[0]['type'])->toBe('file');
            expect($items[0]['path'])->toBe('src/main.ts');
        });

        it('returns empty array on error', function () {
            $mockResponse = new MockResponse('', ['http_code' => 404]);

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $items = $client->listDirectory(\githubProviderPublicOnly(), 'keyxmare/monark', 'nonexistent');

            expect($items)->toBeEmpty();
        });

        it('passes ref parameter in query', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listDirectory(\githubProvider(), 'keyxmare/monark', 'src', 'develop');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('ref=develop');
            expect($url)->toContain('/repos/keyxmare/monark/contents/src');
        });

        it('passes empty path in URL for root directory', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listDirectory(\githubProvider(), 'keyxmare/monark', '', 'main');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/repos/keyxmare/monark/contents/');
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
            expect($prs[0]->status)->not->toBe('closed');
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
            expect($prs[0]->status)->not->toBe('open');
        });

        it('draft takes priority over merged_at', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'number' => 3,
                    'title' => 'Draft with merge',
                    'state' => 'closed',
                    'draft' => true,
                    'head' => ['ref' => 'wip'],
                    'base' => ['ref' => 'main'],
                    'user' => ['login' => 'dev'],
                    'html_url' => 'https://github.com/keyxmare/monark/pull/3',
                    'merged_at' => '2026-03-12T08:00:00Z',
                    'created_at' => '2026-03-12T08:00:00Z',
                    'updated_at' => '2026-03-12T08:00:00Z',
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

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/repos/keyxmare/monark/pulls');
            expect($url)->toContain('state=open');
            expect($url)->toContain('page=2');
            expect($url)->toContain('per_page=10');
        });

        it('maps state "merged" to "closed" for GitHub API', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', 'merged');

            expect($mockResponse->getRequestUrl())->toContain('state=closed');
        });

        it('maps state "closed" to "closed" for GitHub API', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', 'closed');

            expect($mockResponse->getRequestUrl())->toContain('state=closed');
        });

        it('maps state "draft" to "open" for GitHub API', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', 'draft');

            expect($mockResponse->getRequestUrl())->toContain('state=open');
        });

        it('maps unknown state to "all" for GitHub API', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', 'unknown_state');

            expect($mockResponse->getRequestUrl())->toContain('state=all');
        });

        it('uses state=all when state is null', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', null);

            expect($mockResponse->getRequestUrl())->toContain('state=all');
        });

        it('uses state=all when state is "all"', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', 'all');

            expect($mockResponse->getRequestUrl())->toContain('state=all');
        });

        it('caps per_page at 100 for merge requests', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', null, 1, 200);

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('per_page=100');
            expect($url)->not->toContain('per_page=200');
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
            expect($prs[0]->status)->not->toBe('merged');
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
                ],
            ]));

            $client = new GitHubClient(new MockHttpClient($mockResponse));
            $prs = $client->listMergeRequests(\githubProvider(), 'keyxmare/monark');

            expect($prs[0]->description)->toBeNull();
            expect($prs[0]->additions)->toBeNull();
            expect($prs[0]->deletions)->toBeNull();
            expect($prs[0]->reviewers)->toBe([]);
            expect($prs[0]->labels)->toBe([]);
            expect($prs[0]->author)->toBe('');
            expect($prs[0]->url)->toBe('');
            expect($prs[0]->sourceBranch)->toBe('');
            expect($prs[0]->targetBranch)->toBe('');
            expect($prs[0]->status)->toBe('open');
            expect($prs[0]->createdAt)->toBeNull();
            expect($prs[0]->updatedAt)->toBeNull();
        });

        it('passes updatedAfter filter as since parameter', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $after = new \DateTimeImmutable('2026-03-10T10:00:00+00:00');
            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', null, 1, 20, $after);

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('since=');
            expect($url)->toContain('2026-03-10');
            expect($url)->toContain('sort=updated');
            expect($url)->toContain('direction=desc');
        });

        it('does not include since when updatedAfter is null', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'keyxmare/monark', null, 1, 20, null);

            expect($mockResponse->getRequestUrl())->not->toContain('since=');
        });

        it('returns empty array for repo with no PRs', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $prs = $client->listMergeRequests(\githubProvider(), 'keyxmare/monark');

            expect($prs)->toBeEmpty();
        });

        it('builds correct URL with externalProjectId', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitHubClient(new MockHttpClient($mockResponse));

            $client->listMergeRequests(\githubProvider(), 'org/other-repo');

            expect($mockResponse->getRequestUrl())->toContain('/repos/org/other-repo/pulls');
        });
    });
});

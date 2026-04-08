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
    describe('listProjects', function () {
        it('maps GitLab repos to RemoteProject list', function () {
            $mockResponse = new MockResponse(\json_encode([
                [
                    'id' => 42,
                    'name' => 'monark',
                    'path_with_namespace' => 'team/monark',
                    'description' => 'Dev hub',
                    'http_url_to_repo' => 'https://gitlab.example.com/team/monark.git',
                    'web_url' => 'https://gitlab.example.com/team/monark',
                    'default_branch' => 'main',
                    'visibility' => 'public',
                    'avatar_url' => 'https://gitlab.example.com/uploads/avatar.png',
                ],
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $projects = $client->listProjects(\gitlabProvider());

            expect($projects)->toHaveCount(1);
            expect($projects[0]->externalId)->toBe('42');
            expect($projects[0]->name)->toBe('monark');
            expect($projects[0]->slug)->toBe('team/monark');
            expect($projects[0]->description)->toBe('Dev hub');
            expect($projects[0]->repositoryUrl)->toBe('https://gitlab.example.com/team/monark.git');
            expect($projects[0]->defaultBranch)->toBe('main');
            expect($projects[0]->visibility)->toBe('public');
            expect($projects[0]->avatarUrl)->toBe('https://gitlab.example.com/uploads/avatar.png');
        });

        it('sends correct query parameters', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 3, 50, null, null, 'name', 'desc');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/api/v4/projects');
            expect($url)->toContain('membership=true');
            expect($url)->toContain('page=3');
            expect($url)->toContain('per_page=50');
            expect($url)->toContain('order_by=name');
            expect($url)->toContain('sort=desc');
        });

        it('sends PRIVATE-TOKEN header', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider());

            $options = $mockResponse->getRequestOptions();
            $tokenHeader = $options['normalized_headers']['private-token'][0] ?? '';
            expect($tokenHeader)->toContain('glpat-test-token');
        });

        it('includes search parameter when search is provided', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, 'monark');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('search=monark');
        });

        it('does not include search when null', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, null);

            expect($mockResponse->getRequestUrl())->not->toContain('search=');
        });

        it('does not include search when empty string', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, '');

            expect($mockResponse->getRequestUrl())->not->toContain('search=');
        });

        it('includes visibility when provided', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, null, 'private');

            expect($mockResponse->getRequestUrl())->toContain('visibility=private');
        });

        it('does not include visibility when null', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, null, null);

            expect($mockResponse->getRequestUrl())->not->toContain('visibility=');
        });

        it('does not include visibility when empty string', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, null, '');

            expect($mockResponse->getRequestUrl())->not->toContain('visibility=');
        });

        it('maps sort=visibility to order_by=name', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, null, null, 'visibility');

            expect($mockResponse->getRequestUrl())->toContain('order_by=name');
        });

        it('maps sort=defaultBranch to order_by=name', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, null, null, 'defaultBranch');

            expect($mockResponse->getRequestUrl())->toContain('order_by=name');
        });

        it('maps default sort to order_by=name', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider(), 1, 20, null, null, 'anything_else');

            expect($mockResponse->getRequestUrl())->toContain('order_by=name');
        });

        it('returns empty array for no results', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $projects = $client->listProjects(\gitlabProvider());

            expect($projects)->toBeEmpty();
        });

        it('builds correct URL using provider URL', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listProjects(\gitlabProvider());

            expect($mockResponse->getRequestUrl())->toStartWith('https://gitlab.example.com/api/v4/projects');
        });
    });

    describe('countProjects', function () {
        it('returns count from x-total header', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => ['x-total' => '42'],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\gitlabProvider());

            expect($count)->toBe(42);
        });

        it('returns 0 when x-total header is missing', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => [],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $count = $client->countProjects(\gitlabProvider());

            expect($count)->toBe(0);
        });

        it('sends membership=true and per_page=1', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => ['x-total' => '10'],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->countProjects(\gitlabProvider());

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('membership=true');
            expect($url)->toContain('per_page=1');
        });

        it('includes search when provided', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => ['x-total' => '5'],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->countProjects(\gitlabProvider(), 'test');

            expect($mockResponse->getRequestUrl())->toContain('search=test');
        });

        it('does not include search when null', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => ['x-total' => '5'],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->countProjects(\gitlabProvider(), null);

            expect($mockResponse->getRequestUrl())->not->toContain('search=');
        });

        it('does not include search when empty string', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => ['x-total' => '5'],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->countProjects(\gitlabProvider(), '');

            expect($mockResponse->getRequestUrl())->not->toContain('search=');
        });

        it('includes visibility when provided', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => ['x-total' => '3'],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->countProjects(\gitlabProvider(), null, 'private');

            expect($mockResponse->getRequestUrl())->toContain('visibility=private');
        });

        it('does not include visibility when null', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => ['x-total' => '3'],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->countProjects(\gitlabProvider(), null, null);

            expect($mockResponse->getRequestUrl())->not->toContain('visibility=');
        });

        it('does not include visibility when empty string', function () {
            $mockResponse = new MockResponse(\json_encode([]), [
                'response_headers' => ['x-total' => '3'],
            ]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->countProjects(\gitlabProvider(), null, '');

            expect($mockResponse->getRequestUrl())->not->toContain('visibility=');
        });
    });

    describe('testConnection', function () {
        it('returns true on 200 response', function () {
            $mockResponse = new MockResponse(\json_encode(['id' => 1]), ['http_code' => 200]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\gitlabProvider()))->toBeTrue();
            expect($mockResponse->getRequestUrl())->toContain('/api/v4/user');
        });

        it('returns false on non-200 response', function () {
            $mockResponse = new MockResponse('', ['http_code' => 401]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\gitlabProvider()))->toBeFalse();
        });

        it('returns false on 201 (not exactly 200)', function () {
            $mockResponse = new MockResponse('', ['http_code' => 201]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));

            expect($client->testConnection(\gitlabProvider()))->toBeFalse();
        });

        it('returns false on exception', function () {
            $client = new GitLabClient(new MockHttpClient(function () {
                throw new \RuntimeException('Connection failed');
            }));

            expect($client->testConnection(\gitlabProvider()))->toBeFalse();
        });

        it('sends PRIVATE-TOKEN header', function () {
            $mockResponse = new MockResponse(\json_encode([]), ['http_code' => 200]);
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->testConnection(\gitlabProvider());

            $options = $mockResponse->getRequestOptions();
            $tokenHeader = $options['normalized_headers']['private-token'][0] ?? '';
            expect($tokenHeader)->toContain('glpat-test-token');
        });
    });

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
            $options = $mockResponse->getRequestOptions();
            expect($options['normalized_headers']['private-token'][0] ?? '')->toContain('glpat-test-token');
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

        it('returns empty string repositoryUrl when both urls are missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'bare',
                'path_with_namespace' => 'team/bare',
                'default_branch' => 'main',
                'visibility' => 'private',
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\gitlabProvider(), '1');

            expect($project->repositoryUrl)->toBe('');
        });

        it('defaults to main when default_branch is missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'nobranch',
                'path_with_namespace' => 'team/nobranch',
                'http_url_to_repo' => 'https://gitlab.example.com/team/nobranch.git',
                'visibility' => 'private',
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\gitlabProvider(), '1');

            expect($project->defaultBranch)->toBe('main');
        });

        it('defaults visibility to private when missing', function () {
            $mockResponse = new MockResponse(\json_encode([
                'id' => 1,
                'name' => 'novis',
                'path_with_namespace' => 'team/novis',
                'http_url_to_repo' => 'https://gitlab.example.com/team/novis.git',
                'default_branch' => 'main',
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $project = $client->getProject(\gitlabProvider(), '1');

            expect($project->visibility)->toBe('private');
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

    describe('getFileContent', function () {
        it('decodes base64 file content', function () {
            $content = 'Hello World';
            $mockResponse = new MockResponse(\json_encode([
                'content' => \base64_encode($content),
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $result = $client->getFileContent(\gitlabProvider(), '42', 'README.md');

            expect($result)->toBe('Hello World');
        });

        it('builds correct URL with encoded file path', function () {
            $mockResponse = new MockResponse(\json_encode([
                'content' => \base64_encode('test'),
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->getFileContent(\gitlabProvider(), '42', 'src/main.ts', 'develop');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/api/v4/projects/42/repository/files/src%2Fmain.ts');
            expect($url)->toContain('ref=develop');
        });

        it('uses default ref=main', function () {
            $mockResponse = new MockResponse(\json_encode([
                'content' => \base64_encode('test'),
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->getFileContent(\gitlabProvider(), '42', 'file.txt');

            expect($mockResponse->getRequestUrl())->toContain('ref=main');
        });

        it('returns null on 404', function () {
            $mockResponse = new MockResponse('', ['http_code' => 404]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $result = $client->getFileContent(\gitlabProvider(), '42', 'nonexistent.txt');

            expect($result)->toBeNull();
        });

        it('throws on non-404 client errors', function () {
            $mockResponse = new MockResponse('', ['http_code' => 403]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $threw = false;
            try {
                $client->getFileContent(\gitlabProvider(), '42', 'file.txt');
            } catch (\Throwable) {
                $threw = true;
            }
            expect($threw)->toBeTrue();
        });

        it('returns null when base64 decode fails', function () {
            $mockResponse = new MockResponse(\json_encode([
                'content' => '!!!invalid-base64!!!',
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $result = $client->getFileContent(\gitlabProvider(), '42', 'file.txt');

            expect($result)->toBeNull();
        });

        it('sends PRIVATE-TOKEN header', function () {
            $mockResponse = new MockResponse(\json_encode([
                'content' => \base64_encode('test'),
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $client->getFileContent(\gitlabProvider(), '42', 'file.txt');

            $options = $mockResponse->getRequestOptions();
            $tokenHeader = $options['normalized_headers']['private-token'][0] ?? '';
            expect($tokenHeader)->toContain('glpat-test-token');
        });
    });

    describe('listDirectory', function () {
        it('returns mapped directory entries', function () {
            $mockResponse = new MockResponse(\json_encode([
                ['name' => 'src', 'type' => 'tree', 'path' => 'src'],
                ['name' => 'README.md', 'type' => 'blob', 'path' => 'README.md'],
            ]));

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $items = $client->listDirectory(\gitlabProvider(), '42');

            expect($items)->toHaveCount(2);
            expect($items[0])->toBe(['name' => 'src', 'type' => 'tree', 'path' => 'src']);
            expect($items[1])->toBe(['name' => 'README.md', 'type' => 'blob', 'path' => 'README.md']);
        });

        it('builds correct URL', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listDirectory(\gitlabProvider(), '42', 'src', 'develop');

            $url = $mockResponse->getRequestUrl();
            expect($url)->toContain('/api/v4/projects/42/repository/tree');
            expect($url)->toContain('ref=develop');
            expect($url)->toContain('per_page=100');
            expect($url)->toContain('path=src');
        });

        it('does not include path when empty', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listDirectory(\gitlabProvider(), '42', '');

            expect($mockResponse->getRequestUrl())->not->toContain('path=');
        });

        it('includes path when non-empty', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listDirectory(\gitlabProvider(), '42', 'src/modules');

            expect($mockResponse->getRequestUrl())->toContain('path=');
        });

        it('returns empty array on error', function () {
            $mockResponse = new MockResponse('', ['http_code' => 404]);

            $client = new GitLabClient(new MockHttpClient($mockResponse));
            $items = $client->listDirectory(\gitlabProvider(), '42', 'nonexistent');

            expect($items)->toBeEmpty();
        });

        it('uses default ref=main', function () {
            $mockResponse = new MockResponse(\json_encode([]));
            $client = new GitLabClient(new MockHttpClient($mockResponse));

            $client->listDirectory(\gitlabProvider(), '42');

            expect($mockResponse->getRequestUrl())->toContain('ref=main');
        });
    });
});

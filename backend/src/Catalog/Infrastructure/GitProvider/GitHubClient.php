<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\GitProvider;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteMergeRequest;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GitHubClient implements GitProviderInterface
{
    private const array DEFAULT_HEADERS = [
        'Accept' => 'application/vnd.github+json',
        'X-GitHub-Api-Version' => '2022-11-28',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /** @return list<RemoteProject> */
    public function listProjects(Provider $provider, int $page = 1, int $perPage = 20, ?string $search = null, ?string $visibility = null, string $sort = 'name', string $sortDir = 'asc'): array
    {
        $perPage = \min($perPage, 100);

        if ($search !== null && $search !== '') {
            return $this->searchProjects($provider, $search, $visibility, $sort, $sortDir, $page, $perPage);
        }

        $ghSort = match ($sort) {
            'name' => 'full_name',
            default => 'updated',
        };

        if ($this->isAuthenticated($provider)) {
            $url = $this->baseUrl($provider) . '/user/repos';
            $query = ['type' => 'owner', 'sort' => $ghSort, 'direction' => $sortDir, 'page' => $page, 'per_page' => $perPage];
            if ($visibility !== null && $visibility !== '') {
                $query['visibility'] = $visibility;
            }
        } else {
            $url = \sprintf('%s/users/%s/repos', $this->baseUrl($provider), $provider->getUsername());
            $query = ['sort' => $ghSort, 'direction' => $sortDir, 'page' => $page, 'per_page' => $perPage];
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => $this->headers($provider),
            'query' => $query,
        ]);

        /** @var list<array{full_name?: string, name: string, description?: string|null, clone_url?: string, html_url?: string, default_branch?: string, private?: bool, owner?: array{avatar_url?: string}}> $projects */
        $projects = $response->toArray();

        return \array_map(
            static fn (array $p): RemoteProject => self::mapProject($p),
            $projects,
        );
    }

    public function countProjects(Provider $provider, ?string $search = null, ?string $visibility = null): int
    {
        if ($search !== null && $search !== '') {
            $qualifier = $this->isAuthenticated($provider) ? 'user:@me' : \sprintf('user:%s', $provider->getUsername());
            $q = $search . ' ' . $qualifier;
            if ($visibility !== null && $visibility !== '') {
                $q .= ' is:' . $visibility;
            }

            $response = $this->httpClient->request('GET', $this->baseUrl($provider) . '/search/repositories', [
                'headers' => $this->headers($provider),
                'query' => ['q' => $q, 'per_page' => 1],
            ]);

            /** @var array{total_count?: int} $data */
            $data = $response->toArray();

            return $data['total_count'] ?? 0;
        }

        if ($this->isAuthenticated($provider)) {
            $response = $this->httpClient->request('GET', $this->baseUrl($provider) . '/user', [
                'headers' => $this->headers($provider),
            ]);

            /** @var array{public_repos?: int, owned_private_repos?: int} $data */
            $data = $response->toArray();

            if ($visibility === 'private') {
                return $data['owned_private_repos'] ?? 0;
            }
            if ($visibility === 'public') {
                return $data['public_repos'] ?? 0;
            }

            return ($data['public_repos'] ?? 0) + ($data['owned_private_repos'] ?? 0);
        }

        $url = \sprintf('%s/users/%s', $this->baseUrl($provider), $provider->getUsername());
        $response = $this->httpClient->request('GET', $url, [
            'headers' => $this->headers($provider),
        ]);

        /** @var array{public_repos?: int} $data */
        $data = $response->toArray();

        return $data['public_repos'] ?? 0;
    }

    public function testConnection(Provider $provider): bool
    {
        try {
            if ($this->isAuthenticated($provider)) {
                $url = $this->baseUrl($provider) . '/user';
            } else {
                $url = \sprintf('%s/users/%s', $this->baseUrl($provider), $provider->getUsername());
            }

            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->headers($provider),
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getProject(Provider $provider, string $externalId): RemoteProject
    {
        $url = \sprintf('%s/repos/%s', $this->baseUrl($provider), $externalId);

        $response = $this->httpClient->request('GET', $url, [
            'headers' => $this->headers($provider),
        ]);

        /** @var array{full_name?: string, name: string, description?: string|null, clone_url?: string, html_url?: string, default_branch?: string, private?: bool, owner?: array{avatar_url?: string}} $data */
        $data = $response->toArray();

        return self::mapProject($data);
    }

    public function getFileContent(Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string
    {
        $url = \sprintf('%s/repos/%s/contents/%s', $this->baseUrl($provider), $externalProjectId, $filePath);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->headers($provider),
                'query' => ['ref' => $ref],
            ]);

            /** @var array{content?: string} $data */
            $data = $response->toArray();

            return \base64_decode($data['content'] ?? '');
        } catch (ClientExceptionInterface $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $e;
        }
    }

    /** @return list<RemoteMergeRequest> */
    public function listMergeRequests(Provider $provider, string $externalProjectId, ?string $state = null, int $page = 1, int $perPage = 20, ?\DateTimeImmutable $updatedAfter = null): array
    {
        $perPage = \min($perPage, 100);
        $url = \sprintf('%s/repos/%s/pulls', $this->baseUrl($provider), $externalProjectId);

        $query = [
            'page' => $page,
            'per_page' => $perPage,
            'sort' => 'updated',
            'direction' => 'desc',
        ];

        if ($state !== null && $state !== 'all') {
            $query['state'] = match ($state) {
                'open', 'draft' => 'open',
                'merged', 'closed' => 'closed',
                default => 'all',
            };
        } else {
            $query['state'] = 'all';
        }

        if ($updatedAfter !== null) {
            $query['since'] = $updatedAfter->format(\DateTimeInterface::ATOM);
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => $this->headers($provider),
            'query' => $query,
        ]);

        /** @var list<array{state?: string, draft?: bool, merged_at?: string|null, number: int|string, title: string, body?: string|null, head?: array{ref: string}, base?: array{ref: string}, user?: array{login: string}, html_url?: string, additions?: int, deletions?: int, requested_reviewers?: list<array{login: string}>, labels?: list<array{name: string}>, created_at?: string, updated_at?: string, closed_at?: string|null}> $items */
        $items = $response->toArray();

        return \array_map(
            static fn (array $pr): RemoteMergeRequest => self::mapGitHubPullRequest($pr),
            $items,
        );
    }

    /** @param array{state?: string, draft?: bool, merged_at?: string|null, number: int|string, title: string, body?: string|null, head?: array{ref: string}, base?: array{ref: string}, user?: array{login: string}, html_url?: string, additions?: int, deletions?: int, requested_reviewers?: list<array{login: string}>, labels?: list<array{name: string}>, created_at?: string, updated_at?: string, closed_at?: string|null} $pr */
    private static function mapGitHubPullRequest(array $pr): RemoteMergeRequest
    {
        $ghState = $pr['state'] ?? 'open';
        $isDraft = $pr['draft'] ?? false;
        $mergedAt = $pr['merged_at'] ?? null;

        if ($isDraft) {
            $status = 'draft';
        } elseif ($mergedAt !== null) {
            $status = 'merged';
        } elseif ($ghState === 'closed') {
            $status = 'closed';
        } else {
            $status = 'open';
        }

        $reviewers = \array_map(
            static fn (array $r): string => $r['login'],
            $pr['requested_reviewers'] ?? [],
        );

        $labels = \array_map(
            static fn (array $l): string => $l['name'],
            $pr['labels'] ?? [],
        );

        return new RemoteMergeRequest(
            externalId: (string) $pr['number'],
            title: $pr['title'],
            description: $pr['body'] ?? null,
            sourceBranch: ($pr['head'] ?? ['ref' => ''])['ref'],
            targetBranch: ($pr['base'] ?? ['ref' => ''])['ref'],
            status: $status,
            author: ($pr['user'] ?? ['login' => ''])['login'],
            url: $pr['html_url'] ?? '',
            additions: $pr['additions'] ?? null,
            deletions: $pr['deletions'] ?? null,
            reviewers: $reviewers,
            labels: $labels,
            createdAt: $pr['created_at'] ?? null,
            updatedAt: $pr['updated_at'] ?? null,
            mergedAt: $mergedAt,
            closedAt: $pr['closed_at'] ?? null,
        );
    }

    /** @return list<array{name: string, type: string, path: string}> */
    public function listDirectory(Provider $provider, string $externalProjectId, string $path = '', string $ref = 'main'): array
    {
        $url = \sprintf('%s/repos/%s/contents/%s', $this->baseUrl($provider), $externalProjectId, $path);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->headers($provider),
                'query' => ['ref' => $ref],
            ]);

            $rawData = $response->toArray();

            if (!$this->isListResponse($rawData)) {
                /** @var array{name: string, type: string, path: string} $rawData */
                return [['name' => $rawData['name'], 'type' => $rawData['type'], 'path' => $rawData['path']]];
            }

            /** @var list<array{name: string, type: string, path: string}> $rawData */
            return \array_map(
                static fn (array $item): array => [
                    'name' => $item['name'],
                    'type' => match ($item['type']) {
                        'dir' => 'tree',
                        'file' => 'blob',
                        default => $item['type'],
                    },
                    'path' => $item['path'],
                ],
                $rawData,
            );
        } catch (ClientExceptionInterface) {
            return [];
        }
    }

    /** @return list<RemoteProject> */
    private function searchProjects(Provider $provider, string $search, ?string $visibility, string $sort, string $sortDir, int $page, int $perPage): array
    {
        $qualifier = $this->isAuthenticated($provider) ? 'user:@me' : \sprintf('user:%s', $provider->getUsername());
        $q = $search . ' ' . $qualifier;
        if ($visibility !== null && $visibility !== '') {
            $q .= ' is:' . $visibility;
        }

        $ghSort = match ($sort) {
            'name' => 'stars',
            default => 'updated',
        };

        $response = $this->httpClient->request('GET', $this->baseUrl($provider) . '/search/repositories', [
            'headers' => $this->headers($provider),
            'query' => ['q' => $q, 'sort' => $ghSort, 'order' => $sortDir, 'page' => $page, 'per_page' => $perPage],
        ]);

        /** @var array{items?: list<array{full_name?: string, name: string, description?: string|null, clone_url?: string, html_url?: string, default_branch?: string, private?: bool, owner?: array{avatar_url?: string}}>} $responseData */
        $responseData = $response->toArray();

        return \array_map(
            static fn (array $p): RemoteProject => self::mapProject($p),
            $responseData['items'] ?? [],
        );
    }

    /** @param array{full_name?: string, name: string, description?: string|null, clone_url?: string, html_url?: string, default_branch?: string, private?: bool, owner?: array{avatar_url?: string}} $p */
    private static function mapProject(array $p): RemoteProject
    {
        return new RemoteProject(
            externalId: $p['full_name'] ?? $p['name'],
            name: $p['name'],
            slug: $p['full_name'] ?? $p['name'],
            description: $p['description'] ?? null,
            repositoryUrl: $p['clone_url'] ?? $p['html_url'] ?? '',
            defaultBranch: $p['default_branch'] ?? 'main',
            visibility: ($p['private'] ?? false) ? 'private' : 'public',
            avatarUrl: isset($p['owner']) ? ($p['owner']['avatar_url'] ?? '') : null,
        );
    }

    /** @param array<mixed> $data */
    private function isListResponse(array $data): bool
    {
        return \array_is_list($data);
    }

    private function isAuthenticated(Provider $provider): bool
    {
        return $provider->getApiToken() !== null && $provider->getApiToken() !== '';
    }

    private function baseUrl(Provider $provider): string
    {
        $url = $provider->getUrl();

        if (\preg_match('#^https?://github\.com/?$#i', $url)) {
            return 'https://api.github.com';
        }

        return $url;
    }

    /** @return array<string, string> */
    private function headers(Provider $provider): array
    {
        $headers = self::DEFAULT_HEADERS;

        if ($this->isAuthenticated($provider)) {
            $headers['Authorization'] = 'Bearer ' . $provider->getApiToken();
        }

        return $headers;
    }
}

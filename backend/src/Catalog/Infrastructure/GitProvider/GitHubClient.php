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
    public function listProjects(Provider $provider, int $page = 1, int $perPage = 20): array
    {
        $perPage = \min($perPage, 100);

        if ($this->isAuthenticated($provider)) {
            $url = $this->baseUrl($provider) . '/user/repos';
            $query = ['type' => 'owner', 'sort' => 'updated', 'direction' => 'desc', 'page' => $page, 'per_page' => $perPage];
        } else {
            $url = \sprintf('%s/users/%s/repos', $this->baseUrl($provider), $provider->getUsername());
            $query = ['sort' => 'updated', 'direction' => 'desc', 'page' => $page, 'per_page' => $perPage];
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => $this->headers($provider),
            'query' => $query,
        ]);

        /** @var list<array<string, mixed>> $projects */
        $projects = $response->toArray();

        return \array_map(
            static fn (array $p): RemoteProject => new RemoteProject(
                externalId: (string) $p['full_name'],
                name: (string) $p['name'],
                slug: (string) $p['full_name'],
                description: isset($p['description']) ? (string) $p['description'] : null,
                repositoryUrl: (string) ($p['clone_url'] ?? $p['html_url']),
                defaultBranch: (string) ($p['default_branch'] ?? 'main'),
                visibility: (bool) ($p['private'] ?? false) ? 'private' : 'public',
                avatarUrl: isset($p['owner']) && \is_array($p['owner']) ? (string) ($p['owner']['avatar_url'] ?? '') : null,
            ),
            $projects,
        );
    }

    public function countProjects(Provider $provider): int
    {
        if ($this->isAuthenticated($provider)) {
            $response = $this->httpClient->request('GET', $this->baseUrl($provider) . '/user', [
                'headers' => $this->headers($provider),
            ]);
            $data = $response->toArray();

            return (int) ($data['public_repos'] ?? 0) + (int) ($data['owned_private_repos'] ?? 0);
        }

        $url = \sprintf('%s/users/%s', $this->baseUrl($provider), $provider->getUsername());
        $response = $this->httpClient->request('GET', $url, [
            'headers' => $this->headers($provider),
        ]);
        $data = $response->toArray();

        return (int) ($data['public_repos'] ?? 0);
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

        $p = $response->toArray();

        return new RemoteProject(
            externalId: (string) $p['full_name'],
            name: (string) $p['name'],
            slug: (string) $p['full_name'],
            description: isset($p['description']) ? (string) $p['description'] : null,
            repositoryUrl: (string) ($p['clone_url'] ?? $p['html_url']),
            defaultBranch: (string) ($p['default_branch'] ?? 'main'),
            visibility: (bool) ($p['private'] ?? false) ? 'private' : 'public',
            avatarUrl: isset($p['owner']) && \is_array($p['owner']) ? (string) ($p['owner']['avatar_url'] ?? '') : null,
        );
    }

    public function getFileContent(Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string
    {
        $url = \sprintf('%s/repos/%s/contents/%s', $this->baseUrl($provider), $externalProjectId, $filePath);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->headers($provider),
                'query' => ['ref' => $ref],
            ]);

            $data = $response->toArray();

            return \base64_decode((string) $data['content']);
        } catch (ClientExceptionInterface $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $e;
        }
    }

    /** @return list<RemoteMergeRequest> */
    public function listMergeRequests(Provider $provider, string $externalProjectId, ?string $state = null, int $page = 1, int $perPage = 20): array
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

        $response = $this->httpClient->request('GET', $url, [
            'headers' => $this->headers($provider),
            'query' => $query,
        ]);

        return \array_map(
            static fn (array $pr): RemoteMergeRequest => self::mapGitHubPullRequest($pr),
            $response->toArray(),
        );
    }

    private static function mapGitHubPullRequest(array $pr): RemoteMergeRequest
    {
        $ghState = (string) ($pr['state'] ?? 'open');
        $isDraft = (bool) ($pr['draft'] ?? false);
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
            static fn (array $r): string => (string) $r['login'],
            \is_array($pr['requested_reviewers'] ?? null) ? $pr['requested_reviewers'] : [],
        );

        $labels = \array_map(
            static fn (array $l): string => (string) $l['name'],
            \is_array($pr['labels'] ?? null) ? $pr['labels'] : [],
        );

        return new RemoteMergeRequest(
            externalId: (string) ($pr['number'] ?? ''),
            title: (string) ($pr['title'] ?? ''),
            description: isset($pr['body']) ? (string) $pr['body'] : null,
            sourceBranch: (string) ($pr['head']['ref'] ?? ''),
            targetBranch: (string) ($pr['base']['ref'] ?? ''),
            status: $status,
            author: (string) ($pr['user']['login'] ?? ''),
            url: (string) ($pr['html_url'] ?? ''),
            additions: isset($pr['additions']) ? (int) $pr['additions'] : null,
            deletions: isset($pr['deletions']) ? (int) $pr['deletions'] : null,
            reviewers: $reviewers,
            labels: $labels,
            createdAt: isset($pr['created_at']) ? (string) $pr['created_at'] : null,
            updatedAt: isset($pr['updated_at']) ? (string) $pr['updated_at'] : null,
            mergedAt: $mergedAt !== null ? (string) $mergedAt : null,
            closedAt: isset($pr['closed_at']) ? (string) $pr['closed_at'] : null,
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

            $items = $response->toArray();

            if (isset($items['name'])) {
                return [['name' => (string) $items['name'], 'type' => (string) $items['type'], 'path' => (string) $items['path']]];
            }

            /** @var list<array<string, mixed>> $items */
            return \array_map(
                static fn (array $item): array => [
                    'name' => (string) $item['name'],
                    'type' => match ((string) $item['type']) {
                        'dir' => 'tree',
                        'file' => 'blob',
                        default => (string) $item['type'],
                    },
                    'path' => (string) $item['path'],
                ],
                $items,
            );
        } catch (ClientExceptionInterface) {
            return [];
        }
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

<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\GitProvider;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

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
        } catch (Throwable) {
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

            return \base64_decode($data['content'] ?? '', true) ?: null;
        } catch (ClientExceptionInterface $e) {
            $status = $e->getResponse()->getStatusCode();
            if ($status === 404 || $status === 403) {
                return null;
            }

            throw $e;
        }
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

    public function listBranches(Provider $provider, string $externalProjectId): array
    {
        $url = \sprintf('%s/repos/%s/branches', $this->baseUrl($provider), $externalProjectId);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->headers($provider),
                'query' => ['per_page' => 100],
            ]);

            /** @var list<array{name: string}> $data */
            $data = $response->toArray();

            $branches = \array_map(
                static fn (array $b): string => $b['name'],
                $data,
            );
            \sort($branches);

            return $branches;
        } catch (Throwable) {
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

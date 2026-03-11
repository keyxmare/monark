<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\GitProvider;

use App\Catalog\Domain\Model\Provider;
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

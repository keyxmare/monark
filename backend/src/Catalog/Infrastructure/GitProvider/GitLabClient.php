<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\GitProvider;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final readonly class GitLabClient implements GitProviderInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private HttpClientInterface $httpClient,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /** @return list<RemoteProject> */
    public function listProjects(Provider $provider, int $page = 1, int $perPage = 20, ?string $search = null, ?string $visibility = null, string $sort = 'name', string $sortDir = 'asc'): array
    {
        $orderBy = match ($sort) {
            'visibility', 'defaultBranch' => 'name',
            default => 'name',
        };

        $query = [
            'membership' => 'true',
            'page' => $page,
            'per_page' => $perPage,
            'order_by' => $orderBy,
            'sort' => $sortDir,
        ];

        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        if ($visibility !== null && $visibility !== '') {
            $query['visibility'] = $visibility;
        }

        $response = $this->httpClient->request('GET', $provider->getUrl() . '/api/v4/projects', [
            'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
            'query' => $query,
        ]);

        /** @var list<array{id: int|string, name: string, path_with_namespace: string, description?: string|null, http_url_to_repo?: string, web_url?: string, default_branch?: string, visibility?: string, avatar_url?: string|null}> $projects */
        $projects = $response->toArray();

        return \array_map(
            static fn (array $p): RemoteProject => self::mapProject($p),
            $projects,
        );
    }

    public function countProjects(Provider $provider, ?string $search = null, ?string $visibility = null): int
    {
        $query = ['membership' => 'true', 'per_page' => 1];

        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        if ($visibility !== null && $visibility !== '') {
            $query['visibility'] = $visibility;
        }

        $response = $this->httpClient->request('GET', $provider->getUrl() . '/api/v4/projects', [
            'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
            'query' => $query,
        ]);

        return (int) ($response->getHeaders()['x-total'][0] ?? 0);
    }

    public function testConnection(Provider $provider): bool
    {
        try {
            $response = $this->httpClient->request('GET', $provider->getUrl() . '/api/v4/user', [
                'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
            ]);

            return $response->getStatusCode() === 200;
        } catch (Throwable $e) {
            $this->logger->warning('GitLab connection test failed.', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getProject(Provider $provider, string $externalId): RemoteProject
    {
        $url = \sprintf('%s/api/v4/projects/%s', $provider->getUrl(), \rawurlencode($externalId));

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
        ]);

        /** @var array{id: int|string, name: string, path_with_namespace: string, description?: string|null, http_url_to_repo?: string, web_url?: string, default_branch?: string, visibility?: string, avatar_url?: string|null} $p */
        $p = $response->toArray();

        return self::mapProject($p);
    }

    /** @return list<array{name: string, type: string, path: string}> */
    public function listDirectory(Provider $provider, string $externalProjectId, string $path = '', string $ref = 'main'): array
    {
        $url = \sprintf('%s/api/v4/projects/%s/repository/tree', $provider->getUrl(), $externalProjectId);
        $query = ['ref' => $ref, 'per_page' => 100];

        if ($path !== '') {
            $query['path'] = $path;
        }

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
                'query' => $query,
            ]);

            /** @var list<array{name: string, type: string, path: string}> $items */
            $items = $response->toArray();

            return \array_map(
                static fn (array $item): array => [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'path' => $item['path'],
                ],
                $items,
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->warning('GitLab listDirectory failed.', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function getFileContent(Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string
    {
        $encodedPath = \rawurlencode($filePath);
        $url = \sprintf('%s/api/v4/projects/%s/repository/files/%s', $provider->getUrl(), $externalProjectId, $encodedPath);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
                'query' => ['ref' => $ref],
            ]);

            /** @var array{content: string} $data */
            $data = $response->toArray();

            return \base64_decode($data['content'], true) ?: null;
        } catch (ClientExceptionInterface $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $e;
        }
    }

    public function listBranches(Provider $provider, string $externalProjectId): array
    {
        $url = \sprintf('%s/api/v4/projects/%s/repository/branches', $provider->getUrl(), $externalProjectId);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
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

    /** @param array{id: int|string, name: string, path_with_namespace: string, description?: string|null, http_url_to_repo?: string, web_url?: string, default_branch?: string, visibility?: string, avatar_url?: string|null} $p */
    private static function mapProject(array $p): RemoteProject
    {
        return new RemoteProject(
            externalId: (string) $p['id'],
            name: $p['name'],
            slug: $p['path_with_namespace'],
            description: $p['description'] ?? null,
            repositoryUrl: $p['http_url_to_repo'] ?? $p['web_url'] ?? '',
            defaultBranch: $p['default_branch'] ?? 'main',
            visibility: $p['visibility'] ?? 'private',
            avatarUrl: $p['avatar_url'] ?? null,
        );
    }
}

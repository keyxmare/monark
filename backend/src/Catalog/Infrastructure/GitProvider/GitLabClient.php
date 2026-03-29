<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\GitProvider;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteMergeRequest;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderInterface;
use DateTimeImmutable;
use DateTimeInterface;
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

    /** @return list<RemoteMergeRequest> */
    public function listMergeRequests(Provider $provider, string $externalProjectId, ?string $state = null, int $page = 1, int $perPage = 20, ?DateTimeImmutable $updatedAfter = null): array
    {
        $url = \sprintf('%s/api/v4/projects/%s/merge_requests', $provider->getUrl(), \rawurlencode($externalProjectId));

        $query = [
            'page' => $page,
            'per_page' => $perPage,
            'order_by' => 'updated_at',
            'sort' => 'desc',
        ];

        if ($state !== null) {
            $query['state'] = match ($state) {
                'open' => 'opened',
                'merged' => 'merged',
                'closed' => 'closed',
                default => 'all',
            };
        } else {
            $query['state'] = 'all';
        }

        if ($updatedAfter !== null) {
            $query['updated_after'] = $updatedAfter->format(DateTimeInterface::ATOM);
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
            'query' => $query,
        ]);

        /** @var list<array{state?: string, draft?: bool, iid: int|string, title: string, description?: string|null, source_branch: string, target_branch: string, author?: array{username: string}, web_url?: string, reviewers?: list<array{username: string}>, labels?: list<string>, created_at?: string, updated_at?: string, merged_at?: string|null, closed_at?: string|null}> $items */
        $items = $response->toArray();

        return \array_map(
            static fn (array $mr): RemoteMergeRequest => self::mapGitLabMergeRequest($mr),
            $items,
        );
    }

    /** @param array{state?: string, draft?: bool, iid: int|string, title: string, description?: string|null, source_branch: string, target_branch: string, author?: array{username: string}, web_url?: string, reviewers?: list<array{username: string}>, labels?: list<string>, created_at?: string, updated_at?: string, merged_at?: string|null, closed_at?: string|null} $mr */
    private static function mapGitLabMergeRequest(array $mr): RemoteMergeRequest
    {
        $state = (string) ($mr['state'] ?? 'opened');
        $isDraft = $mr['draft'] ?? false;

        if ($isDraft && $state === 'opened') {
            $status = 'draft';
        } else {
            $status = match ($state) {
                'opened' => 'open',
                'merged' => 'merged',
                'closed' => 'closed',
                default => 'open',
            };
        }

        $reviewers = \array_map(
            static fn (array $r): string => $r['username'],
            $mr['reviewers'] ?? [],
        );

        $labels = $mr['labels'] ?? [];

        return new RemoteMergeRequest(
            externalId: (string) $mr['iid'],
            title: $mr['title'],
            description: $mr['description'] ?? null,
            sourceBranch: $mr['source_branch'],
            targetBranch: $mr['target_branch'],
            status: $status,
            author: ($mr['author'] ?? ['username' => ''])['username'],
            url: $mr['web_url'] ?? '',
            additions: null,
            deletions: null,
            reviewers: $reviewers,
            labels: $labels,
            createdAt: $mr['created_at'] ?? null,
            updatedAt: $mr['updated_at'] ?? null,
            mergedAt: $mr['merged_at'] ?? null,
            closedAt: $mr['closed_at'] ?? null,
        );
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

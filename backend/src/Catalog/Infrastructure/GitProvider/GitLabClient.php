<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\GitProvider;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteMergeRequest;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GitLabClient implements GitProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /** @return list<RemoteProject> */
    public function listProjects(Provider $provider, int $page = 1, int $perPage = 20): array
    {
        $response = $this->httpClient->request('GET', $provider->getUrl() . '/api/v4/projects', [
            'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
            'query' => [
                'membership' => 'true',
                'page' => $page,
                'per_page' => $perPage,
                'order_by' => 'last_activity_at',
                'sort' => 'desc',
            ],
        ]);

        $projects = $response->toArray();

        return \array_map(
            static fn (array $p) => new RemoteProject(
                externalId: (string) $p['id'],
                name: $p['name'],
                slug: $p['path_with_namespace'],
                description: $p['description'] ?? null,
                repositoryUrl: $p['http_url_to_repo'] ?? $p['web_url'],
                defaultBranch: $p['default_branch'] ?? 'main',
                visibility: $p['visibility'] ?? 'private',
                avatarUrl: $p['avatar_url'] ?? null,
            ),
            $projects,
        );
    }

    public function countProjects(Provider $provider): int
    {
        $response = $this->httpClient->request('GET', $provider->getUrl() . '/api/v4/projects', [
            'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
            'query' => ['membership' => 'true', 'per_page' => 1],
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
        } catch (\Throwable) {
            return false;
        }
    }

    public function getProject(Provider $provider, string $externalId): RemoteProject
    {
        $url = \sprintf('%s/api/v4/projects/%s', $provider->getUrl(), \rawurlencode($externalId));

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
        ]);

        $p = $response->toArray();

        return new RemoteProject(
            externalId: (string) $p['id'],
            name: $p['name'],
            slug: $p['path_with_namespace'],
            description: $p['description'] ?? null,
            repositoryUrl: $p['http_url_to_repo'] ?? $p['web_url'],
            defaultBranch: $p['default_branch'] ?? 'main',
            visibility: $p['visibility'] ?? 'private',
            avatarUrl: $p['avatar_url'] ?? null,
        );
    }

    /** @return list<RemoteMergeRequest> */
    public function listMergeRequests(Provider $provider, string $externalProjectId, ?string $state = null, int $page = 1, int $perPage = 20, ?\DateTimeImmutable $updatedAfter = null): array
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
            $query['updated_after'] = $updatedAfter->format(\DateTimeInterface::ATOM);
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
            'query' => $query,
        ]);

        return \array_map(
            static fn (array $mr): RemoteMergeRequest => self::mapGitLabMergeRequest($mr),
            $response->toArray(),
        );
    }

    private static function mapGitLabMergeRequest(array $mr): RemoteMergeRequest
    {
        $state = (string) ($mr['state'] ?? 'opened');
        $isDraft = (bool) ($mr['draft'] ?? false);

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
            static fn (array $r): string => (string) $r['username'],
            \is_array($mr['reviewers'] ?? null) ? $mr['reviewers'] : [],
        );

        $labels = \array_map(
            static fn (mixed $l): string => (string) $l,
            \is_array($mr['labels'] ?? null) ? $mr['labels'] : [],
        );

        return new RemoteMergeRequest(
            externalId: (string) $mr['iid'],
            title: (string) $mr['title'],
            description: isset($mr['description']) ? (string) $mr['description'] : null,
            sourceBranch: (string) $mr['source_branch'],
            targetBranch: (string) $mr['target_branch'],
            status: $status,
            author: (string) ($mr['author']['username'] ?? ''),
            url: (string) ($mr['web_url'] ?? ''),
            additions: null,
            deletions: null,
            reviewers: $reviewers,
            labels: $labels,
            createdAt: isset($mr['created_at']) ? (string) $mr['created_at'] : null,
            updatedAt: isset($mr['updated_at']) ? (string) $mr['updated_at'] : null,
            mergedAt: isset($mr['merged_at']) ? (string) $mr['merged_at'] : null,
            closedAt: isset($mr['closed_at']) ? (string) $mr['closed_at'] : null,
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

            return \array_map(
                static fn (array $item): array => [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'path' => $item['path'],
                ],
                $response->toArray(),
            );
        } catch (ClientExceptionInterface) {
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

            $data = $response->toArray();

            return \base64_decode($data['content']);
        } catch (ClientExceptionInterface $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $e;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Port;

use App\Monitoring\Catalog\Domain\Model\Provider;
use App\Monitoring\Catalog\Domain\Model\RemoteCommit;
use App\Monitoring\Catalog\Domain\Model\RemoteProject;
use DateTimeImmutable;

interface GitProviderInterface
{
    /** @return list<RemoteProject> */
    public function listProjects(Provider $provider, int $page = 1, int $perPage = 20, ?string $search = null, ?string $visibility = null, string $sort = 'name', string $sortDir = 'asc'): array;

    public function countProjects(Provider $provider, ?string $search = null, ?string $visibility = null): int;

    public function testConnection(Provider $provider): bool;

    public function getProject(Provider $provider, string $externalId): RemoteProject;

    public function getFileContent(Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string;

    /** @return list<array{name: string, type: string, path: string}> */
    public function listDirectory(Provider $provider, string $externalProjectId, string $path = '', string $ref = 'main'): array;

    /** @return list<string> */
    public function listBranches(Provider $provider, string $externalProjectId): array;

    /**
     * Byte count per language, as reported by the provider's code analysis (GitHub Linguist / GitLab equivalent).
     *
     * @return array<string, int> Keyed by language name, descending bytes
     */
    public function getRepositoryLanguages(Provider $provider, string $externalProjectId): array;

    /**
     * Commits on the default branch authored after `$since`. Capped at ~300 commits (3 pages × 100).
     *
     * @return list<RemoteCommit>
     */
    public function getRecentCommits(Provider $provider, string $externalProjectId, DateTimeImmutable $since): array;
}

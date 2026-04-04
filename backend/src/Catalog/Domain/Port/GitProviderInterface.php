<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Port;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteProject;

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
}

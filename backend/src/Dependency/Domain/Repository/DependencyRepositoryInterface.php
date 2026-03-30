<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Repository;

use App\Dependency\Domain\Model\Dependency;
use Symfony\Component\Uid\Uuid;

interface DependencyRepositoryInterface
{
    public function findById(Uuid $id): ?Dependency;

    /** @return list<Dependency> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    /** @return list<Dependency> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array;

    public function save(Dependency $dependency): void;

    public function delete(Dependency $dependency): void;

    public function countByProjectId(Uuid $projectId): int;

    public function deleteByProjectId(Uuid $projectId): void;

    /**
     * @param array{projectId?: string, search?: string, packageManager?: string, type?: string, isOutdated?: bool, sort?: string, sortDir?: string} $filters
     * @return list<Dependency>
     */
    public function findFiltered(int $page, int $perPage, array $filters = []): array;

    /** @param array{projectId?: string, search?: string, packageManager?: string, type?: string, isOutdated?: bool} $filters */
    public function countFiltered(array $filters = []): int;

    /** @return list<array{name: string, packageManager: string}> */
    public function findUniquePackages(): array;

    /** @return list<Dependency> */
    public function findByName(string $name, string $packageManager): array;

    public function findByNameManagerAndProjectId(string $name, string $packageManager, Uuid $projectId): ?Dependency;

    /**
     * @param array{projectId?: string, packageManager?: string, type?: string} $filters
     * @return array{total: int, outdated: int, totalVulnerabilities: int}
     */
    public function getStats(array $filters = []): array;

    /**
     * @param array{projectId?: string, packageManager?: string, type?: string} $filters
     * @return array{total: int, outdated: int, totalVulnerabilities: int}
     */
    public function getStatsSingle(array $filters = []): array;

    /**
     * @param array{projectId?: string, search?: string, packageManager?: string, type?: string, isOutdated?: bool, sort?: string, sortDir?: string} $filters
     * @return list<array{dependency: Dependency, currentVersionReleasedAt: ?string, latestVersionReleasedAt: ?string}>
     */
    public function findFilteredWithVersionDates(int $page, int $perPage, array $filters = []): array;
}

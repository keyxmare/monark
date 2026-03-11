<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Project;
use Symfony\Component\Uid\Uuid;

interface ProjectRepositoryInterface
{
    public function findById(Uuid $id): ?Project;

    public function findBySlug(string $slug): ?Project;

    public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project;

    /** @return list<string> */
    public function findExternalIdsByProvider(Uuid $providerId): array;

    /** @return list<Project> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function save(Project $project): void;

    public function delete(Project $project): void;
}

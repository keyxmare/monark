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
}

<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\TechStack;
use Symfony\Component\Uid\Uuid;

interface TechStackRepositoryInterface
{
    public function findById(Uuid $id): ?TechStack;

    /** @return list<TechStack> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    /** @return list<TechStack> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array;

    public function countByProjectId(Uuid $projectId): int;

    public function count(): int;

    public function save(TechStack $techStack): void;

    public function delete(TechStack $techStack): void;

    public function deleteByProjectId(Uuid $projectId): void;
}

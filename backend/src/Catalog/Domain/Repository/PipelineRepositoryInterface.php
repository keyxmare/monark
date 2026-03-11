<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Pipeline;
use Symfony\Component\Uid\Uuid;

interface PipelineRepositoryInterface
{
    public function findById(Uuid $id): ?Pipeline;

    /** @return list<Pipeline> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    /** @return list<Pipeline> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, ?string $ref = null): array;

    public function countByProjectId(Uuid $projectId, ?string $ref = null): int;

    public function count(): int;

    public function save(Pipeline $pipeline): void;
}

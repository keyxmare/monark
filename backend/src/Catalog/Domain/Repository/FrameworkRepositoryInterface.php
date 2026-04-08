<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Framework;
use Symfony\Component\Uid\Uuid;

interface FrameworkRepositoryInterface
{
    public function findById(Uuid $id): ?Framework;

    /** @return list<Framework> */
    public function findAll(): array;

    /** @return list<Framework> */
    public function findByProjectId(Uuid $projectId): array;

    public function findByNameAndProjectId(string $name, Uuid $projectId): ?Framework;

    /** @return list<Framework> */
    public function findByName(string $name): array;

    public function save(Framework $framework): void;

    public function delete(Framework $framework): void;

    public function deleteByProjectId(Uuid $projectId): void;
}

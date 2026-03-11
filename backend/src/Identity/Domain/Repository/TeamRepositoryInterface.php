<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Model\Team;
use Symfony\Component\Uid\Uuid;

interface TeamRepositoryInterface
{
    public function findById(Uuid $id): ?Team;

    public function findBySlug(string $slug): ?Team;

    /** @return list<Team> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function save(Team $team): void;

    public function delete(Team $team): void;
}

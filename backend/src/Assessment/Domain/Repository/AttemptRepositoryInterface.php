<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Repository;

use App\Assessment\Domain\Model\Attempt;
use Symfony\Component\Uid\Uuid;

interface AttemptRepositoryInterface
{
    public function findById(Uuid $id): ?Attempt;

    /** @return list<Attempt> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function save(Attempt $attempt): void;
}

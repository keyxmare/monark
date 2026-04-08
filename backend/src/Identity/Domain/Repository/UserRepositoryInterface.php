<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Model\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function findById(Uuid $id): ?User;

    public function findByEmail(string $email): ?User;

    /** @return list<User> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function save(User $user): void;
}

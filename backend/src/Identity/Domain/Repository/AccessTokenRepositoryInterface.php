<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Model\AccessToken;
use Symfony\Component\Uid\Uuid;

interface AccessTokenRepositoryInterface
{
    public function findById(Uuid $id): ?AccessToken;

    /** @return list<AccessToken> */
    public function findByUser(Uuid $userId, int $page = 1, int $perPage = 20): array;

    public function countByUser(Uuid $userId): int;

    public function save(AccessToken $accessToken): void;

    public function delete(AccessToken $accessToken): void;
}

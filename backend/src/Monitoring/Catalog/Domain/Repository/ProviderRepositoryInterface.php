<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Repository;

use App\Monitoring\Catalog\Domain\Model\Provider;
use Symfony\Component\Uid\Uuid;

interface ProviderRepositoryInterface
{
    public function findById(Uuid $id): ?Provider;

    /** @return list<Provider> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function save(Provider $provider): void;

    public function remove(Provider $provider): void;
}

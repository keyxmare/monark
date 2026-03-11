<?php

declare(strict_types=1);

namespace App\Activity\Domain\Repository;

use App\Activity\Domain\Model\ActivityEvent;
use Symfony\Component\Uid\Uuid;

interface ActivityEventRepositoryInterface
{
    public function findById(Uuid $id): ?ActivityEvent;

    /** @return list<ActivityEvent> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function save(ActivityEvent $event): void;
}

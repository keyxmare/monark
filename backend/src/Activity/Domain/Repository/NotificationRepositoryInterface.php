<?php

declare(strict_types=1);

namespace App\Activity\Domain\Repository;

use App\Activity\Domain\Model\Notification;
use Symfony\Component\Uid\Uuid;

interface NotificationRepositoryInterface
{
    public function findById(Uuid $id): ?Notification;

    /** @return list<Notification> */
    public function findByUser(string $userId, int $page = 1, int $perPage = 20): array;

    public function countByUser(string $userId): int;

    public function countUnreadByUser(string $userId): int;

    public function save(Notification $notification): void;
}

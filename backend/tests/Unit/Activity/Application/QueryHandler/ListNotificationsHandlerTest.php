<?php

declare(strict_types=1);

use App\Activity\Application\DTO\NotificationListOutput;
use App\Activity\Application\Query\ListNotificationsQuery;
use App\Activity\Application\QueryHandler\ListNotificationsHandler;
use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListNotificationsRepo(array $notifications = [], int $count = 0): NotificationRepositoryInterface
{
    return new class ($notifications, $count) implements NotificationRepositoryInterface {
        public function __construct(private readonly array $notifications, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?Notification
        {
            return null;
        }
        public function findByUser(string $userId, int $page = 1, int $perPage = 20): array
        {
            return $this->notifications;
        }
        public function countByUser(string $userId): int
        {
            return $this->count;
        }
        public function countUnreadByUser(string $userId): int
        {
            return 0;
        }
        public function save(Notification $notification): void
        {
        }
    };
}

describe('ListNotificationsHandler', function () {
    it('returns paginated notifications for a user', function () {
        $notification = Notification::create(
            title: 'Test',
            message: 'Body',
            channel: NotificationChannel::InApp,
            userId: '00000000-0000-0000-0000-000000000001',
        );

        $handler = new ListNotificationsHandler(\stubListNotificationsRepo([$notification], 1));
        $result = $handler(new ListNotificationsQuery('00000000-0000-0000-0000-000000000001'));

        expect($result)->toBeInstanceOf(NotificationListOutput::class);
        expect($result->pagination->items)->toHaveCount(1);
        expect($result->pagination->total)->toBe(1);
    });

    it('returns empty list when no notifications', function () {
        $handler = new ListNotificationsHandler(\stubListNotificationsRepo([], 0));
        $result = $handler(new ListNotificationsQuery('00000000-0000-0000-0000-000000000001'));

        expect($result->pagination->items)->toHaveCount(0);
        expect($result->pagination->total)->toBe(0);
    });
});

<?php

declare(strict_types=1);

use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Application\Query\GetNotificationQuery;
use App\Activity\Application\QueryHandler\GetNotificationHandler;
use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetNotificationRepo(?Notification $notification = null): NotificationRepositoryInterface
{
    return new class ($notification) implements NotificationRepositoryInterface {
        public function __construct(private readonly ?Notification $notification)
        {
        }
        public function findById(Uuid $id): ?Notification
        {
            return $this->notification;
        }
        public function findByUser(string $userId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function countByUser(string $userId): int
        {
            return 0;
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

describe('GetNotificationHandler', function () {
    it('returns a notification by id', function () {
        $notification = Notification::create(
            title: 'Test Alert',
            message: 'Something happened.',
            channel: NotificationChannel::InApp,
            userId: '00000000-0000-0000-0000-000000000001',
        );

        $handler = new GetNotificationHandler(\stubGetNotificationRepo($notification));
        $result = $handler(new GetNotificationQuery($notification->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(NotificationOutput::class);
        expect($result->title)->toBe('Test Alert');
        expect($result->channel)->toBe('in_app');
    });

    it('throws not found when notification does not exist', function () {
        $handler = new GetNotificationHandler(\stubGetNotificationRepo(null));
        $handler(new GetNotificationQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});

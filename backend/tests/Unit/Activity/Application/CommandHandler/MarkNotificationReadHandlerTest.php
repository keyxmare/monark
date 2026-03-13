<?php

declare(strict_types=1);

use App\Activity\Application\Command\MarkNotificationReadCommand;
use App\Activity\Application\CommandHandler\MarkNotificationReadHandler;
use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubMarkReadNotificationRepo(?Notification $notification = null): NotificationRepositoryInterface
{
    return new class ($notification) implements NotificationRepositoryInterface {
        public ?Notification $saved = null;
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
            $this->saved = $notification;
        }
    };
}

describe('MarkNotificationReadHandler', function () {
    it('marks a notification as read', function () {
        $notification = Notification::create(
            title: 'Test',
            message: 'Body',
            channel: NotificationChannel::InApp,
            userId: '00000000-0000-0000-0000-000000000001',
        );

        $repo = \stubMarkReadNotificationRepo($notification);
        $handler = new MarkNotificationReadHandler($repo);

        $result = $handler(new MarkNotificationReadCommand($notification->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(NotificationOutput::class);
        expect($result->readAt)->not->toBeNull();
        expect($repo->saved)->not->toBeNull();
    });

    it('throws not found when notification does not exist', function () {
        $handler = new MarkNotificationReadHandler(\stubMarkReadNotificationRepo(null));
        $handler(new MarkNotificationReadCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});

<?php

declare(strict_types=1);

use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(NotificationRepositoryInterface::class);
});

function createNotification(
    string $title = 'Test Notification',
    string $message = 'This is a test notification',
    NotificationChannel $channel = NotificationChannel::InApp,
    string $userId = 'user-1',
): Notification {
    return Notification::create(
        title: $title,
        message: $message,
        channel: $channel,
        userId: $userId,
    );
}

describe('DoctrineNotificationRepository', function () {
    it('saves and finds a notification by id', function () {
        $notification = createNotification();
        $this->repo->save($notification);

        $found = $this->repo->findById($notification->getId());

        expect($found)->not->toBeNull();
        expect($found->getTitle())->toBe('Test Notification');
        expect($found->getMessage())->toBe('This is a test notification');
        expect($found->getChannel())->toBe(NotificationChannel::InApp);
        expect($found->getUserId())->toBe('user-1');
        expect($found->getReadAt())->toBeNull();
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(Uuid::v7()))->toBeNull();
    });

    it('finds notifications by user with pagination', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->repo->save(createNotification(title: "Notification {$i}", userId: 'user-1'));
        }
        $this->repo->save(createNotification(title: 'Other User', userId: 'user-2'));

        $page1 = $this->repo->findByUser('user-1', page: 1, perPage: 3);
        expect($page1)->toHaveCount(3);

        $page2 = $this->repo->findByUser('user-1', page: 2, perPage: 3);
        expect($page2)->toHaveCount(2);
    });

    it('counts notifications by user', function () {
        $this->repo->save(createNotification(userId: 'user-1'));
        $this->repo->save(createNotification(userId: 'user-1'));
        $this->repo->save(createNotification(userId: 'user-2'));

        expect($this->repo->countByUser('user-1'))->toBe(2);
        expect($this->repo->countByUser('user-2'))->toBe(1);
        expect($this->repo->countByUser('user-3'))->toBe(0);
    });

    it('counts unread notifications by user', function () {
        $read = createNotification(title: 'Read', userId: 'user-1');
        $read->markAsRead();
        $this->repo->save($read);

        $this->repo->save(createNotification(title: 'Unread 1', userId: 'user-1'));
        $this->repo->save(createNotification(title: 'Unread 2', userId: 'user-1'));
        $this->repo->save(createNotification(title: 'Other user', userId: 'user-2'));

        expect($this->repo->countUnreadByUser('user-1'))->toBe(2);
        expect($this->repo->countUnreadByUser('user-2'))->toBe(1);
    });
});

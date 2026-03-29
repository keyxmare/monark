<?php

declare(strict_types=1);

use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;
use App\Tests\Factory\Activity\NotificationFactory;
use Symfony\Component\Uid\Uuid;

describe('Notification', function () {
    it('creates with all fields via factory', function () {
        $notification = NotificationFactory::create();

        expect($notification->getId())->toBeInstanceOf(Uuid::class);
        expect($notification->getTitle())->toBe('Test Notification');
        expect($notification->getMessage())->toBe('This is a test notification.');
        expect($notification->getChannel())->toBe(NotificationChannel::InApp);
        expect($notification->getUserId())->toBe('00000000-0000-0000-0000-000000000001');
        expect($notification->getReadAt())->toBeNull();
        expect($notification->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates with email channel', function () {
        $notification = NotificationFactory::create([
            'channel' => NotificationChannel::Email,
        ]);

        expect($notification->getChannel())->toBe(NotificationChannel::Email);
    });

    it('creates with custom values', function () {
        $notification = Notification::create(
            title: 'CVE Alert',
            message: 'Critical vulnerability found.',
            channel: NotificationChannel::Email,
            userId: 'user-99',
        );

        expect($notification->getTitle())->toBe('CVE Alert');
        expect($notification->getMessage())->toBe('Critical vulnerability found.');
        expect($notification->getChannel())->toBe(NotificationChannel::Email);
        expect($notification->getUserId())->toBe('user-99');
    });

    it('marks as read', function () {
        $notification = NotificationFactory::create();

        expect($notification->getReadAt())->toBeNull();

        $notification->markAsRead();

        expect($notification->getReadAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('marks as read with current time', function () {
        $notification = NotificationFactory::create();

        $before = new \DateTimeImmutable();
        $notification->markAsRead();
        $after = new \DateTimeImmutable();

        expect($notification->getReadAt() >= $before)->toBeTrue();
        expect($notification->getReadAt() <= $after)->toBeTrue();
    });

    it('generates unique ids', function () {
        $n1 = NotificationFactory::create();
        $n2 = NotificationFactory::create();

        expect($n1->getId()->equals($n2->getId()))->toBeFalse();
    });

    it('rejects blank title', function () {
        expect(fn () => Notification::create(
            title: '',
            message: 'Some message',
            channel: NotificationChannel::InApp,
            userId: 'user-1',
        ))->toThrow(\InvalidArgumentException::class, 'title must not be blank');
    });

    it('rejects blank message', function () {
        expect(fn () => Notification::create(
            title: 'Some title',
            message: '',
            channel: NotificationChannel::InApp,
            userId: 'user-1',
        ))->toThrow(\InvalidArgumentException::class, 'message must not be blank');
    });
});

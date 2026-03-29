<?php

declare(strict_types=1);

use App\Activity\Domain\Model\NotificationChannel;

describe('NotificationChannel', function () {
    it('has exactly 2 cases', function () {
        expect(NotificationChannel::cases())->toHaveCount(2);
    });

    it('has correct values', function () {
        expect(NotificationChannel::InApp->value)->toBe('in_app');
        expect(NotificationChannel::Email->value)->toBe('email');
    });

    it('creates from valid string', function () {
        expect(NotificationChannel::from('in_app'))->toBe(NotificationChannel::InApp);
        expect(NotificationChannel::from('email'))->toBe(NotificationChannel::Email);
    });

    it('returns null for invalid string via tryFrom', function () {
        expect(NotificationChannel::tryFrom('sms'))->toBeNull();
        expect(NotificationChannel::tryFrom(''))->toBeNull();
    });

    it('throws on invalid string via from', function () {
        expect(fn () => NotificationChannel::from('push'))->toThrow(\ValueError::class);
    });
});

<?php

declare(strict_types=1);

use App\Identity\Domain\Event\UserCreated;
use App\Identity\Domain\Event\UserUpdated;

describe('UserCreated', function () {
    it('holds event data', function () {
        $event = new UserCreated(
            userId: 'user-123',
            email: 'john@example.com',
        );

        expect($event->userId)->toBe('user-123');
        expect($event->email)->toBe('john@example.com');
    });
});

describe('UserUpdated', function () {
    it('holds event data', function () {
        $event = new UserUpdated(
            userId: 'user-456',
        );

        expect($event->userId)->toBe('user-456');
    });
});

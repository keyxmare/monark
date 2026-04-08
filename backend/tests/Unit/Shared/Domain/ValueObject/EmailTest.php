<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\Email;

describe('Email', function () {
    it('creates with a valid email', function () {
        $email = new Email('user@example.com');

        expect($email->value())->toBe('user@example.com');
    });

    it('throws on invalid email', function () {
        new Email('not-an-email');
    })->throws(\InvalidArgumentException::class);

    it('throws on empty string', function () {
        new Email('');
    })->throws(\InvalidArgumentException::class);

    it('converts to string', function () {
        $email = new Email('user@example.com');

        expect((string) $email)->toBe('user@example.com');
    });

    it('compares equality', function () {
        $a = new Email('user@example.com');
        $b = new Email('user@example.com');
        $c = new Email('other@example.com');

        expect($a->equals($b))->toBeTrue();
        expect($a->equals($c))->toBeFalse();
    });
});

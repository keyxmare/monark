<?php

declare(strict_types=1);

use App\Identity\Domain\Service\PasswordPolicy;
use App\Identity\Domain\ValueObject\PasswordStrength;

describe('PasswordPolicy', function () {
    it('validates a strong password', function () {
        $policy = new PasswordPolicy();

        $strength = $policy->assess('MyStr0ng!Password');

        expect($strength)->toBe(PasswordStrength::Strong);
    });

    it('returns fair for valid but short password', function () {
        $policy = new PasswordPolicy();

        $strength = $policy->assess('Abc1!xyz');

        expect($strength)->toBe(PasswordStrength::Fair);
    });

    it('throws when password is too short', function () {
        $policy = new PasswordPolicy();

        $policy->enforce('Short1!');
    })->throws(\InvalidArgumentException::class);

    it('throws when no uppercase', function () {
        $policy = new PasswordPolicy();

        $policy->enforce('nouppercas3!');
    })->throws(\InvalidArgumentException::class);

    it('throws when no digit', function () {
        $policy = new PasswordPolicy();

        $policy->enforce('NoDigitHere!');
    })->throws(\InvalidArgumentException::class);

    it('throws when no special character', function () {
        $policy = new PasswordPolicy();

        $policy->enforce('NoSpecial1A');
    })->throws(\InvalidArgumentException::class);

    it('assess returns weak for invalid password', function () {
        $policy = new PasswordPolicy();

        $strength = $policy->assess('weak');

        expect($strength)->toBe(PasswordStrength::Weak);
    });
});

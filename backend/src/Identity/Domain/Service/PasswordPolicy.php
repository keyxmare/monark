<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

use App\Identity\Domain\ValueObject\PasswordStrength;
use InvalidArgumentException;

final class PasswordPolicy
{
    private const int MIN_LENGTH = 8;
    private const int STRONG_LENGTH = 12;

    public function enforce(string $plainPassword): void
    {
        if (\strlen($plainPassword) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(\sprintf('Password must be at least %d characters long.', self::MIN_LENGTH));
        }

        if (!\preg_match('/[A-Z]/', $plainPassword)) {
            throw new InvalidArgumentException('Password must contain at least one uppercase letter.');
        }

        if (!\preg_match('/[0-9]/', $plainPassword)) {
            throw new InvalidArgumentException('Password must contain at least one digit.');
        }

        if (!\preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:\',.<>?]/', $plainPassword)) {
            throw new InvalidArgumentException('Password must contain at least one special character.');
        }
    }

    public function assess(string $plainPassword): PasswordStrength
    {
        try {
            $this->enforce($plainPassword);
        } catch (InvalidArgumentException) {
            return PasswordStrength::Weak;
        }

        if (\strlen($plainPassword) >= self::STRONG_LENGTH) {
            return PasswordStrength::Strong;
        }

        return PasswordStrength::Fair;
    }
}

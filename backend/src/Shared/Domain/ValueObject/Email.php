<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final readonly class Email implements \Stringable
{
    public function __construct(private string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException(\sprintf('Invalid email address: "%s".', $value));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

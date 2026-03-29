<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final readonly class Slug implements \Stringable
{
    public function __construct(private string $value)
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) !== 1) {
            throw new \InvalidArgumentException(\sprintf('Invalid slug: "%s".', $value));
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

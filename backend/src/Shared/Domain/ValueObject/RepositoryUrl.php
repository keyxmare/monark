<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class RepositoryUrl implements Stringable
{
    public function __construct(private string $value)
    {
        if (\filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(\sprintf('Invalid repository URL: "%s".', $value));
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

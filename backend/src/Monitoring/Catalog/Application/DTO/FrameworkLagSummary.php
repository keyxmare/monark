<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\DTO;

final readonly class FrameworkLagSummary
{
    public function __construct(
        public int $major,
        public int $minor,
        public int $patch,
        public int $upToDate,
        public int $unknown,
    ) {
    }

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0);
    }

    public function total(): int
    {
        return $this->major + $this->minor + $this->patch + $this->upToDate + $this->unknown;
    }
}
